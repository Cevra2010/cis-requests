<?php

namespace App\Services\Workflow\Actions;

use App\Models\Project;
use App\Models\ProjectStatusTransition;
use App\Models\WorkflowAction;

/**
 * Maßnahme "Springen zu Schritt". config-Form: { "target": "next" | "previous" | <Schritt-Code> }.
 * Der Sprung ist ein automatischer Statuswechsel (kein Freigeber nötig, im Verlauf als "automatisch"
 * vermerkt) und löst – wie jedes Erreichen eines Schritts – dessen Maßnahmen aus. Eine Tiefengrenze
 * verhindert Endlosschleifen, falls Schritte einander gegenseitig anspringen.
 */
class GotoStepAction implements WorkflowActionType
{
    private const MAX_DEPTH = 8;

    private static int $depth = 0;

    public function key(): string
    {
        return 'goto_step';
    }

    public function label(): string
    {
        return 'Springen zu Schritt';
    }

    public function icon(): string
    {
        return 'share';
    }

    public function editable(): bool
    {
        return true;
    }

    public function editorView(): string
    {
        return 'livewire.workflow.actions.goto-step-fields';
    }

    public function defaultConfig(): array
    {
        return ['target' => 'next'];
    }

    public function validate(array $config): array
    {
        return trim((string) ($config['target'] ?? '')) === '' ? ['target' => 'Bitte einen Zielschritt wählen.'] : [];
    }

    public function summary(array $config): string
    {
        return 'Springen zu: ' . self::targetLabel((string) ($config['target'] ?? ''));
    }

    public static function targetLabel(string $target, ?string $projectType = null): string
    {
        return match ($target) {
            'next'     => 'nächster Schritt',
            'previous' => 'vorheriger Schritt',
            ''         => 'noch nicht gewählt',
            default    => ($projectType
                ? \App\Models\WorkflowStatus::where('project_type', $projectType)->where('code', $target)->value('label')
                : \App\Models\WorkflowStatus::where('code', $target)->value('label')) ?? $target,
        };
    }

    public function execute(Project $project, WorkflowAction $action, ?ProjectStatusTransition $transition): array
    {
        $target = (string) ($action->config['target'] ?? '');

        $step = match ($target) {
            'next'     => $project->nextStep(),
            'previous' => $project->previousStep(),
            ''         => null,
            default    => $project->workflow()->step($target),
        };

        if (! $step) {
            return ['status' => 'skipped', 'summary' => 'Kein Zielschritt vorhanden.', 'details' => null];
        }
        if ($step->code === $project->status_code) {
            return ['status' => 'skipped', 'summary' => 'Projekt steht bereits auf „' . $step->label . '“.', 'details' => null];
        }
        if (self::$depth >= self::MAX_DEPTH) {
            return ['status' => 'failed', 'summary' => 'Abgebrochen: Schleife zwischen Workflow-Schritten erkannt.', 'details' => null];
        }

        self::$depth++;
        try {
            $project->jumpToStep($step);
        } finally {
            self::$depth--;
        }

        return ['status' => 'done', 'summary' => 'Gesprungen zu „' . $step->label . '“.', 'details' => null];
    }
}
