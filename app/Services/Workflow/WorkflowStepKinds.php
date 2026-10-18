<?php

namespace App\Services\Workflow;

use Nwidart\Modules\Facades\Module;

/**
 * Feste Registry der Schritt-"Kinds": jeder Kind trägt einen Rang (für die
 * Reihenfolge-Prüfung in WorkflowValidator), ob er Pflicht ist, ein optionales
 * Modul, an das er gebunden ist, und optionale Meilensteine (was das Erreichen
 * dieses Schritts freischaltet). "custom" ist der einzige Kind, der beliebig
 * oft vorkommen darf und keinen festen Rang hat (siehe WorkflowValidator).
 */
class WorkflowStepKinds
{
    public const MILESTONE_OFFERS        = 'offers';
    public const MILESTONE_AWARDING      = 'awarding';
    public const MILESTONE_GOODS_RECEIPT = 'goods_receipt';

    /** @var array<string, array{label:string, rank:int, required:bool, first?:bool, last?:bool, module:?string, milestones:string[], color:string, default_tab:?string}> */
    protected static array $kinds = [
        'draft' => [
            'label' => 'Entwurf', 'rank' => 10, 'required' => true, 'first' => true,
            'module' => null, 'milestones' => [], 'color' => 'gray', 'default_tab' => 'beladung',
        ],
        'ready_for_tender' => [
            'label' => 'Bereit zur Ausschreibung', 'rank' => 20, 'required' => false,
            'module' => null, 'milestones' => [], 'color' => 'blue', 'default_tab' => 'ausschreibung',
        ],
        'tender' => [
            'label' => 'Ausschreibung', 'rank' => 30, 'required' => true,
            'module' => null, 'milestones' => [self::MILESTONE_OFFERS],
            'color' => 'amber', 'default_tab' => 'angebote',
        ],
        'ready_for_evaluation' => [
            'label' => 'Bereit zur Auswertung', 'rank' => 40, 'required' => false,
            'module' => null, 'milestones' => [], 'color' => 'orange', 'default_tab' => 'angebote',
        ],
        'evaluated' => [
            'label' => 'Ausgewertet', 'rank' => 50, 'required' => true,
            'module' => null, 'milestones' => [self::MILESTONE_AWARDING],
            'color' => 'cyan', 'default_tab' => 'bestellung',
        ],
        'ordered' => [
            'label' => 'Bestellt', 'rank' => 60, 'required' => true,
            'module' => null, 'milestones' => [self::MILESTONE_GOODS_RECEIPT],
            'color' => 'indigo', 'default_tab' => 'wareneingang',
        ],
        'orders_in_revision' => [
            'label' => 'Bestellungen in Revision', 'rank' => 70, 'required' => false,
            'module' => null, 'milestones' => [], 'color' => 'red', 'default_tab' => 'wareneingang',
        ],
        'completed' => [
            'label' => 'Abgeschlossen', 'rank' => 90, 'required' => true, 'last' => true,
            'module' => null, 'milestones' => [], 'color' => 'green', 'default_tab' => 'wareneingang',
        ],
    ];

    /** @return array<string, array{label:string,rank:int,required:bool}> */
    public static function all(): array
    {
        return self::$kinds;
    }

    public static function find(string $kind): ?array
    {
        return self::$kinds[$kind] ?? null;
    }

    public static function rank(string $kind): int
    {
        return self::$kinds[$kind]['rank'] ?? PHP_INT_MAX;
    }

    public static function isRequired(string $kind): bool
    {
        return self::$kinds[$kind]['required'] ?? false;
    }

    public static function isFirst(string $kind): bool
    {
        return self::$kinds[$kind]['first'] ?? false;
    }

    public static function isLast(string $kind): bool
    {
        return self::$kinds[$kind]['last'] ?? false;
    }

    public static function module(string $kind): ?string
    {
        return self::$kinds[$kind]['module'] ?? null;
    }

    /** @return string[] */
    public static function milestones(string $kind): array
    {
        return self::$kinds[$kind]['milestones'] ?? [];
    }

    public static function hasMilestone(string $kind, string $milestone): bool
    {
        return in_array($milestone, self::milestones($kind), true);
    }

    public static function defaultColor(string $kind): string
    {
        return self::$kinds[$kind]['color'] ?? 'gray';
    }

    public static function defaultTab(string $kind): ?string
    {
        return self::$kinds[$kind]['default_tab'] ?? null;
    }

    public static function label(string $kind): string
    {
        return self::$kinds[$kind]['label'] ?? 'Eigener Schritt';
    }

    public static function isModuleEnabled(string $kind): bool
    {
        $module = self::module($kind);

        return $module === null || (Module::find($module)?->isEnabled() ?? false);
    }

    /** Kinds (Schlüssel + Definition), die noch nicht in $usedKinds vorkommen – für die Bausteine-Palette. */
    public static function availableToAdd(array $usedKinds): array
    {
        return collect(self::$kinds)
            ->reject(fn ($def, $kind) => in_array($kind, $usedKinds, true))
            ->all();
    }
}
