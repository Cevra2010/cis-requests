<?php

namespace App\Http\Livewire\Workflow;

use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Services\Workflow\Actions\WorkflowActionTypes;
use App\Services\Workflow\ProjectVariables;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal zum Konfigurieren einer Workflow-Maßnahme (aktuell: "E-Mail senden").
 * Wird von WorkflowEditor per "edit-workflow-action"-Event geöffnet (neu
 * angelegte Maßnahmen öffnen sich direkt beim Ablegen aus der Palette).
 */
class ActionEditor extends Component
{
    public bool $show = false;
    public ?string $actionId = null;
    public string $actionLabel = '';
    public string $actionTypeKey = '';

    /** Konfiguration für Maßnahmenarten ohne eigene Felder-Properties (alle außer "email"). */
    public array $config = [];

    /** @var array<int, array{type:string, value:string, label:string}> */
    public array $recipients = [];
    public string $subject = '';
    public string $body = '';

    public array $formErrors = [];

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasPermission('workflow.manage'), 403);
    }

    #[On('edit-workflow-action')]
    public function open(string $id): void
    {
        $this->authorizeManage();

        $action = WorkflowAction::find($id);
        if (! $action) {
            return;
        }

        $type              = WorkflowActionTypes::find($action->type);
        $this->actionId    = $id;
        $this->actionLabel = $type?->label() ?? $action->type;
        $this->actionTypeKey = $action->type;
        $this->config      = $action->config ?? [];

        $config = $action->config ?? [];
        $this->recipients = collect($config['recipients'] ?? [])
            ->map(fn (array $r) => ['type' => $r['type'] ?? '', 'value' => (string) ($r['value'] ?? ''), 'label' => $this->resolveLabel($r['type'] ?? '', (string) ($r['value'] ?? ''))])
            ->all();
        $this->subject = (string) ($config['subject'] ?? '');
        $this->body    = (string) ($config['body'] ?? '');
        $this->formErrors = [];
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    #[On('principal-picked')]
    public function addRecipient(string $context, string $type, string $id): void
    {
        if ($context !== 'action-recipients' || ! in_array($type, ['user', 'group', 'role', 'email'], true)) {
            return;
        }

        $this->recipients[] = ['type' => $type, 'value' => $id, 'label' => $this->resolveLabel($type, $id)];
    }

    public function removeRecipient(int $index): void
    {
        unset($this->recipients[$index]);
        $this->recipients = array_values($this->recipients);
    }

    public function insertVariable(string $target, string $key): void
    {
        $placeholder = '{{' . $key . '}}';
        if ($target === 'subject') {
            $this->subject = trim($this->subject . ' ' . $placeholder);
        } else {
            $this->body = trim($this->body . ' ' . $placeholder);
        }
    }

    public function save(): void
    {
        $this->authorizeManage();
        $action = $this->actionId ? WorkflowAction::find($this->actionId) : null;
        if (! $action) {
            return;
        }

        $type = WorkflowActionTypes::find($action->type);
        if (! $type) {
            return;
        }

        $config = $action->type === 'email'
            ? [
                'recipients' => collect($this->recipients)->map(fn (array $r) => ['type' => $r['type'], 'value' => $r['value']])->all(),
                'subject'    => $this->subject,
                'body'       => $this->body,
            ]
            : $this->config;

        $errors = $type->validate($config);
        if (! empty($errors)) {
            $this->formErrors = $errors;
            return;
        }

        $action->update(['config' => $config, 'is_active' => true]);
        $this->formErrors = [];
        $this->show = false;
        $this->dispatch('workflow-action-saved');
    }

    private function resolveLabel(string $type, string $value): string
    {
        return match ($type) {
            'user'  => User::find($value)?->name() ?? 'Gelöschter Benutzer',
            'group' => 'Gruppe „' . (Group::find($value)?->name ?? 'gelöscht') . '“',
            'role'  => 'Rolle „' . (Role::find($value)?->name ?? 'gelöscht') . '“',
            'email' => $value,
            default => '?',
        };
    }

    public function render()
    {
        $action = $this->actionId ? WorkflowAction::with('status')->find($this->actionId) : null;
        $type   = $action ? WorkflowActionTypes::find($action->type) : null;

        return view('livewire.workflow.action-editor', [
            'variables'  => ProjectVariables::catalog(),
            'editorView' => $type?->editorView(),
            'actionIcon' => $type?->icon() ?? 'bolt',
            'stepOptions' => $action?->status
                ? \App\Models\WorkflowStatus::where('project_type', $action->status->project_type)->orderBy('sort_order')->get(['code', 'label', 'is_active', 'cis_row_id'])
                : collect(),
            'ownStepId'  => $action?->cis_row_id_workflow_status,
        ]);
    }
}
