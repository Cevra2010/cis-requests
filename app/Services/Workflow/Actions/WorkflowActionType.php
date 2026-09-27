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

    /** @return array<string, mixed> */
    public function defaultConfig(): array;

    /** @return array<string, string> Feld => Fehlermeldung; leer = gültig. */
    public function validate(array $config): array;

    /** Kurzbeschreibung für Palette/Liste/Bestätigungs-Modal. */
    public function summary(array $config): string;

    /** @return array{status: string, summary: ?string, details: ?array} */
    public function execute(Project $project, WorkflowAction $action, ?ProjectStatusTransition $transition): array;
}
