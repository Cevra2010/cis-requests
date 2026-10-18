<?php

namespace App\Services\Workflow\Actions;

use App\Models\Project;
use App\Models\ProjectStatusTransition;
use App\Models\WorkflowAction;

/**
 * Fixierungs-Maßnahme ("Produkte fixiert", "Ausschreibung fixiert", "Angebote fixiert", "Bestellung fixiert").
 *
 * Sie ist eine Markierung am Schritt "Beim Erreichen": ab diesem Schritt – bis zum Ende des Workflows – ist der Bereich
 * (siehe SCOPES) für alle gesperrt. Gesperrt ist ein Projekt immer genau dann, wenn es auf oder hinter dem Schritt steht;
 * beim Zurücksetzen vor diesen Schritt öffnet sich die Fixierung daher von selbst wieder (siehe WorkflowDefinition::isFixed()).
 * execute() fügt nur den Protokolleintrag hinzu – die Wirkung selbst wird nicht ausgeführt, sondern abgeleitet.
 */
class FixationAction implements WorkflowActionType
{
    /** Reihenfolge ist zugleich die logische Reihenfolge der Fixierungen im Workflow. */
    public const SCOPES = [
        'products' => ['label' => 'Produkte fixiert',       'what' => 'Produkte',      'detail' => 'Produkte und deren Preise (Fahrzeug-Konfiguration) sind gesperrt.'],
        'tender'   => ['label' => 'Ausschreibung fixiert',  'what' => 'Ausschreibung', 'detail' => 'Der Ausschreibungstext ist gesperrt.'],
        'offers'   => ['label' => 'Angebote fixiert',       'what' => 'Angebote',      'detail' => 'Angebote und Preise im Angebotsvergleich sind gesperrt.'],
        'awarding' => ['label' => 'Bestellung fixiert',     'what' => 'Bestellung',    'detail' => 'Die Bestellzuordnung ist gesperrt.'],
    ];

    public function __construct(private string $scope)
    {
    }

    public static function typeKey(string $scope): string
    {
        return 'fix_' . $scope;
    }

    /** Fixierungsbereich zu einem Maßnahmen-Typ-Schlüssel (z.B. "fix_offers" → "offers"), sonst null. */
    public static function scopeOf(string $type): ?string
    {
        $scope = str_starts_with($type, 'fix_') ? substr($type, 4) : null;

        return $scope !== null && isset(self::SCOPES[$scope]) ? $scope : null;
    }

    public static function what(string $scope): string
    {
        return self::SCOPES[$scope]['what'] ?? $scope;
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function key(): string
    {
        return self::typeKey($this->scope);
    }

    public function label(): string
    {
        return self::SCOPES[$this->scope]['label'];
    }

    public function icon(): string
    {
        return 'lock';
    }

    public function editable(): bool
    {
        return false;
    }

    public function editorView(): string
    {
        return '';
    }

    public function defaultConfig(): array
    {
        return [];
    }

    public function validate(array $config): array
    {
        return [];
    }

    public function summary(array $config): string
    {
        return $this->label();
    }

    public function execute(Project $project, WorkflowAction $action, ?ProjectStatusTransition $transition): array
    {
        return ['status' => 'done', 'summary' => $this->label() . ' – ' . self::SCOPES[$this->scope]['detail'], 'details' => null];
    }
}
