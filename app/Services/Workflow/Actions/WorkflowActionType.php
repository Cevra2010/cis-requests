<?php

namespace App\Services\Workflow\Actions;

use App\Models\Project;
use App\Models\ProjectStatusTransition;
use App\Models\WorkflowAction;

/**
 * Eine Workflow-Maßnahmenart (aktuell nur "E-Mail senden", siehe EmailAction).
 * Künftige Module registrieren eigene Typen in ihrem ServiceProvider (siehe
 * WorkflowActionTypes::register()) – laufen also nur, wenn das Modul aktiv ist.
 */
interface WorkflowActionType
{
    public function key(): string;

    public function label(): string;

    public function icon(): string;

    /** Hat die Maßnahme Einstellungen, die im Maßnahmen-Editor bearbeitet werden (sonst ist sie eine reine Markierung)? */
    public function editable(): bool;

    /** Blade-View mit den typspezifischen Feldern im Maßnahmen-Editor (siehe ActionEditor). */
    public function editorView(): string;

    /** @return array<string, mixed> */
    public function defaultConfig(): array;

    /** @return array<string, string> Feld => Fehlermeldung; leer = gültig. */
    public function validate(array $config): array;

    /** Kurzbeschreibung für Palette/Liste/Bestätigungs-Modal. */
    public function summary(array $config): string;

    /** @return array{status: string, summary: ?string, details: ?array} */
    public function execute(Project $project, WorkflowAction $action, ?ProjectStatusTransition $transition): array;
}
