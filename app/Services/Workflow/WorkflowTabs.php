<?php

namespace App\Services\Workflow;

use App\Models\Project;
use App\Models\WorkflowStatus;
use Nwidart\Modules\Facades\Module;

/**
 * Welche Tabs eine Projektseite je Workflow-Schritt zeigt. Zwei Ebenen:
 *  1. logische Verfügbarkeit (availability()): ein Tab kann nur sichtbar sein, wenn sein Meilenstein im Workflow schon
 *     erreicht ist, sein Modul aktiv ist und er zum Projekttyp passt – nicht abwählbar/ausgegraut in der Workflow-Konfiguration;
 *  2. Verbergen je Schritt (WorkflowStatus::hidden_tabs): was der Admin auf einem Schritt zusätzlich ausblendet.
 * Einmal erreichte Meilensteine bleiben danach logisch verfügbar (zurückschauen ist immer möglich).
 */
class WorkflowTabs
{
    /** Tab-Schlüssel in Anzeigereihenfolge der Konfiguration. */
    public static function keys(): array
    {
        return array_keys(WorkflowDefaults::TABS);
    }

    public static function label(string $tab, string $projectType): string
    {
        return $tab === 'beladung' && $projectType === 'vehicle' ? 'Fahrzeug-Konfiguration' : (WorkflowDefaults::TABS[$tab] ?? $tab);
    }

    /** @return array<string, array{available: bool, reason: ?string}> */
    public static function availability(WorkflowDefinition $workflow, ?string $code, string $projectType): array
    {
        $milestone = function (string $milestone, string $what) use ($workflow, $code) {
            $step = $workflow->milestoneStep($milestone);
            if (! $step) {
                return ['available' => false, 'reason' => "Kein aktiver Schritt im Workflow schaltet {$what} frei."];
            }
            $reached = $workflow->hasReached($code, $milestone);

            return ['available' => $reached, 'reason' => $reached ? null : "Erst ab dem Schritt „{$step->label}“ verfügbar."];
        };

        $wareneingang = Module::find('Wareneingang')?->isEnabled() ?? false;
        $lager        = Module::find('Lager')?->isEnabled() ?? false;

        return [
            'beladung'      => ['available' => true, 'reason' => null],
            'ausschreibung' => $projectType === 'vehicle'
                ? ['available' => false, 'reason' => 'Fahrzeugausschreibungen haben keinen Ausschreibungs-Tab.']
                : ['available' => true, 'reason' => null],
            'angebote'      => $milestone(WorkflowStepKinds::MILESTONE_OFFERS, 'den Tab „Angebote“'),
            'bestellung'    => $milestone(WorkflowStepKinds::MILESTONE_AWARDING, 'den Tab „Bestellung“'),
            'wareneingang'  => $wareneingang
                ? $milestone(WorkflowStepKinds::MILESTONE_GOODS_RECEIPT, 'den Tab „Wareneingang“')
                : ['available' => false, 'reason' => 'Das Modul „Wareneingang“ ist nicht aktiv.'],
            'lager'         => $lager
                ? ['available' => true, 'reason' => null]
                : ['available' => false, 'reason' => 'Das Modul „Lager“ ist nicht aktiv.'],
        ];
    }

    /**
     * Tabs, die auf diesem Schritt tatsächlich sichtbar sind (verfügbar und nicht verborgen).
     *
     * @return array<string, bool>
     */
    public static function visibleFor(WorkflowDefinition $workflow, ?WorkflowStatus $step, string $projectType): array
    {
        $hidden = $step?->hiddenTabs() ?? [];

        return collect(self::availability($workflow, $step?->code, $projectType))
            ->map(fn (array $a, string $tab) => $a['available'] && ! in_array($tab, $hidden, true))
            ->all();
    }

    /** @return array<string, bool> */
    public static function visible(Project $project): array
    {
        return self::visibleFor($project->workflow(), $project->currentStep(), $project->type ?: 'product');
    }
}
