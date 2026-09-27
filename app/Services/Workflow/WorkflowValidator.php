<?php

namespace App\Services\Workflow;

use App\Models\WorkflowStatus;

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
        if ($status->auto_advance_on_due && ! WorkflowStepKinds::allowsAutoAdvance($status->kind)) {
            $warnings[] = 'Auto-Vorrücken ist nur beim Schritt "Ausschreibung" wirksam.';
        }

        return $warnings;
    }
}
