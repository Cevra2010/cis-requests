<?php

namespace App\Services\Workflow;

use App\Models\WorkflowStatus;
use App\Services\Workflow\Actions\WorkflowActionTypes;

/**
 * Prüft eine KANDIDATEN-Liste von Schritten (vor dem Speichern) auf die Regeln
 * für "logische" Workflows. Rein, DB-frei – arbeitet auf einfachen Arrays
 * [['kind' => string], ...] in der vorgeschlagenen Reihenfolge.
 */
class WorkflowValidator
{
    /**
     * @param array<int, array{kind:string}> $orderedKinds
     * @return string[] Fehlermeldungen (leer = gültig)
     */
    public static function errors(array $orderedKinds): array
    {
        if (empty($orderedKinds)) {
            return ['Der Workflow benötigt mindestens einen Schritt.'];
        }

        $errors = [];

        if (($orderedKinds[0]['kind'] ?? null) !== 'draft') {
            $errors[] = 'Der erste Schritt muss "Entwurf" sein.';
        }
        if ((end($orderedKinds)['kind'] ?? null) !== 'completed') {
            $errors[] = 'Der letzte Schritt muss "Abgeschlossen" sein.';
        }

        $counts = [];
        foreach ($orderedKinds as $step) {
            if ($step['kind'] === 'custom') {
                continue;
            }
            $counts[$step['kind']] = ($counts[$step['kind']] ?? 0) + 1;
        }

        foreach (['draft', 'tender', 'evaluated', 'ordered', 'completed'] as $required) {
            if (($counts[$required] ?? 0) !== 1) {
                $errors[] = '"' . WorkflowStepKinds::label($required) . '" muss genau einmal vorkommen.';
            }
        }
        foreach ($counts as $kind => $count) {
            if ($count > 1 && ! WorkflowStepKinds::isRequired($kind)) {
                $errors[] = '"' . WorkflowStepKinds::label($kind) . '" darf nur einmal vorkommen.';
            }
        }

        // Kernregel: Ränge dürfen entlang der Reihenfolge nicht fallen (nur Nicht-Custom-Schritte
        // tragen einen festen Rang) – das ist die geforderte "nur logische Workflows"-Regel.
        $lastRank  = null;
        $lastLabel = null;
        foreach ($orderedKinds as $step) {
            if ($step['kind'] === 'custom') {
                continue;
            }
            $rank  = WorkflowStepKinds::rank($step['kind']);
            $label = WorkflowStepKinds::label($step['kind']);

            if ($lastRank !== null && $rank < $lastRank) {
                $errors[] = '"' . $label . '" darf nicht vor "' . $lastLabel . '" stehen (logische Reihenfolge: '
                    . $lastLabel . ' → ' . $label . ').';
            }

            $lastRank  = $rank;
            $lastLabel = $label;
        }

        return array_values(array_unique($errors));
    }

    /** @return string[] */
    public static function warnings(WorkflowStatus $status): array
    {
        $warnings = [];

        if ($status->approval_mode === 'restricted' && $status->approvers->isEmpty()) {
            $warnings[] = 'Für diesen Schritt ist kein Freigeber hinterlegt – niemand kann ihn freigeben.';
        }
        foreach ($status->approvers as $approver) {
            if ($approver->isDangling()) {
                $warnings[] = 'Ein hinterlegter Freigeber existiert nicht mehr.';
                break;
            }
        }
        if (! $status->moduleEnabled()) {
            $warnings[] = 'Das zugehörige Modul ist deaktiviert – dieser Schritt hat aktuell keine Wirkung.';
        }

        $allActions = $status->actions->concat($status->triggers->flatMap(fn ($t) => $t->actions));
        foreach ($allActions as $action) {
            $type = WorkflowActionTypes::find($action->type);
            if ($type && ! empty($type->validate($action->config ?? []))) {
                $warnings[] = 'Eine Maßnahme ("' . $type->label() . '") ist noch nicht vollständig konfiguriert.';
                break;
            }
        }
        foreach ($allActions->where('type', 'goto_step') as $action) {
            $target = (string) ($action->config['target'] ?? '');
            if ($target !== '' && ! in_array($target, ['next', 'previous'], true)
                && ! WorkflowStatus::where('project_type', $status->project_type)->where('code', $target)->exists()) {
                $warnings[] = 'Eine Maßnahme „Springen zu Schritt“ verweist auf einen Schritt, den es nicht mehr gibt.';
                break;
            }
        }
        foreach ($status->triggers as $trigger) {
            if (! \App\Services\Workflow\Events\WorkflowEventTypes::find($trigger->event)) {
                $warnings[] = 'Ein Trigger verweist auf eine nicht mehr verfügbare Ereignisart.';
                break;
            }
        }

        return $warnings;
    }
}
