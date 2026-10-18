<?php

namespace App\Services\Workflow\Actions;

use App\Mail\WorkflowNotificationMail;
use App\Models\Group;
use App\Models\Project;
use App\Models\ProjectStatusTransition;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Services\Workflow\ProjectVariables;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Maßnahme "E-Mail senden". config-Form:
 *   { "recipients": [{"type":"user"|"group"|"role"|"email", "value": string}, ...],
 *     "subject": string, "body": string }
 * Empfänger werden zu einzelnen E-Mail-Adressen aufgelöst (Gruppen/Rollen -> Mitglieder),
 * dedupliziert, und jede Adresse einzeln versendet – ein fehlgeschlagener
 * Empfänger blockiert die übrigen nicht (siehe execute()).
 */
class EmailAction implements WorkflowActionType
{
    public function key(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return 'E-Mail senden';
    }

    public function icon(): string
    {
        return 'envelope';
    }

    public function editable(): bool
    {
        return true;
    }

    public function editorView(): string
    {
        return 'livewire.workflow.actions.email-fields';
    }

    public function defaultConfig(): array
    {
        return ['recipients' => [], 'subject' => '', 'body' => ''];
    }

    public function validate(array $config): array
    {
        $errors = [];

        if (empty($config['recipients'])) {
            $errors['recipients'] = 'Mindestens ein Empfänger ist erforderlich.';
        }
        if (trim((string) ($config['subject'] ?? '')) === '') {
            $errors['subject'] = 'Betreff ist erforderlich.';
        }
        if (trim((string) ($config['body'] ?? '')) === '') {
            $errors['body'] = 'Text ist erforderlich.';
        }

        return $errors;
    }

    public function summary(array $config): string
    {
        $n = count($config['recipients'] ?? []);
        $subject = trim((string) ($config['subject'] ?? ''));

        return $n === 0
            ? 'E-Mail senden (noch nicht konfiguriert)'
            : 'E-Mail an ' . $n . ' Empfänger' . ($subject !== '' ? ': ' . Str::limit($subject, 40) : '');
    }

    public function execute(Project $project, WorkflowAction $action, ?ProjectStatusTransition $transition): array
    {
        $config  = $action->config ?? [];
        $subject = ProjectVariables::render((string) ($config['subject'] ?? ''), $project, $transition);
        $body    = ProjectVariables::render((string) ($config['body'] ?? ''), $project, $transition);

        $emails = $this->resolveRecipients($config['recipients'] ?? []);

        if (empty($emails)) {
            return ['status' => 'skipped', 'summary' => 'Keine gültigen Empfänger.', 'details' => []];
        }

        $details = [];
        $sent    = 0;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new WorkflowNotificationMail($subject, $body, $project));
                $details[] = ['email' => $email, 'ok' => true];
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $details[] = ['email' => $email, 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        $status = match (true) {
            $sent === count($emails) => 'sent',
            $sent > 0                => 'partial',
            default                  => 'failed',
        };

        return [
            'status'  => $status,
            'summary' => "{$sent} von " . count($emails) . ' E-Mail(s) versendet',
            'details' => $details,
        ];
    }

    /** @return string[] deduplizierte E-Mail-Adressen */
    private function resolveRecipients(array $recipients): array
    {
        $emails = [];

        foreach ($recipients as $r) {
            $type  = $r['type'] ?? null;
            $value = (string) ($r['value'] ?? '');

            if ($type === 'user') {
                $u = User::find($value);
                if ($u?->email) {
                    $emails[] = $u->email;
                }
            } elseif ($type === 'group') {
                foreach (Group::find($value)?->users ?? [] as $u) {
                    if ($u->email) {
                        $emails[] = $u->email;
                    }
                }
            } elseif ($type === 'role') {
                foreach (Role::find($value)?->users ?? [] as $u) {
                    if ($u->email) {
                        $emails[] = $u->email;
                    }
                }
            } elseif ($type === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $value;
            }
        }

        return array_values(array_unique(array_map('mb_strtolower', $emails)));
    }
}
