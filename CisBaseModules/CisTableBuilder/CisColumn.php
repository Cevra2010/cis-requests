<?php

namespace CisFoundation\CisTableBuilder;

/**
 * CisColumn beschreibt eine einzelne Spalte in einer CisTable.
 *
 * Module können eigene Spalten mit eigener Render-Logik definieren:
 *
 *   CisColumn::make('min_quality', 'Mindestqualität')
 *       ->render(fn($row) => $row->minQuality?->label ?? '–')
 *       ->sortable()
 *       ->after('name')
 *       ->badge('green', fn($row) => $row->minQuality !== null);
 */
class CisColumn
{
    protected string $key;
    protected string $label;
    protected bool $sortable = false;
    protected ?\Closure $renderer = null;
    protected ?\Closure $visibleWhen = null;
    protected ?string $width = null;
    protected ?string $after = null;
    protected int $priority = 10;

    // Badge-Unterstützung: Farbe und optionale Bedingung
    protected ?string $badgeColor = null;
    protected ?\Closure $badgeCondition = null;

    // Gibt an ob die Spalte HTML ausgeben darf (unsafe)
    protected bool $rawHtml = false;

    private function __construct() {}

    /**
     * Factory-Methode – primärer Einstiegspunkt.
     */
    public static function make(string $key, string $label): static
    {
        $col        = new static();
        $col->key   = $key;
        $col->label = $label;
        return $col;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Konfiguration
    // ────────────────────────────────────────────────────────────────────────

    public function sortable(bool $value = true): static
    {
        $this->sortable = $value;
        return $this;
    }

    /**
     * Closure erhält die aktuelle Zeile ($row) und gibt einen String zurück.
     * Standard-escaping wird angewandt, außer rawHtml() wurde gesetzt.
     */
    public function render(\Closure $callback): static
    {
        $this->renderer = $callback;
        return $this;
    }

    /**
     * Erlaubt der Render-Closure rohes HTML zurückzugeben.
     * Nur verwenden wenn der Inhalt vertrauenswürdig ist.
     */
    public function rawHtml(bool $value = true): static
    {
        $this->rawHtml = $value;
        return $this;
    }

    /**
     * Rendert den Wert als Badge einer bestimmten Farbe.
     * Optionale $condition-Closure: fn($row) => bool – nur wenn true wird Badge gezeigt.
     *
     * Farben: blue, green, red, yellow, gray, purple
     */
    public function badge(string $color = 'blue', ?\Closure $condition = null): static
    {
        $this->badgeColor     = $color;
        $this->badgeCondition = $condition;
        return $this;
    }

    /**
     * Gibt die Spalte nach dem Spalte mit dem angegebenen Key ein.
     */
    public function after(string $columnKey): static
    {
        $this->after = $columnKey;
        return $this;
    }

    /**
     * Setzt die Position direkt (kleinere Zahl = weiter links).
     */
    public function priority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Spalte nur anzeigen wenn die Closure true zurückgibt.
     * fn() => auth()->user()->can('see-prices')
     */
    public function visibleWhen(\Closure $condition): static
    {
        $this->visibleWhen = $condition;
        return $this;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Rendering
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Rendert den Zellinhalt für eine gegebene Zeile.
     * Gibt einen sicheren String zurück (HTML-escaped), außer rawHtml ist gesetzt.
     */
    public function renderCell(mixed $row): string
    {
        $value = $this->resolveValue($row);

        if ($this->badgeColor !== null) {
            $show = $this->badgeCondition === null || ($this->badgeCondition)($row);
            if ($show) {
                $colorMap = [
                    'blue'   => 'bg-blue-100 text-blue-700',
                    'green'  => 'bg-green-100 text-green-700',
                    'red'    => 'bg-red-100 text-red-700',
                    'yellow' => 'bg-yellow-100 text-yellow-700',
                    'gray'   => 'bg-gray-100 text-gray-600',
                    'purple' => 'bg-purple-100 text-purple-700',
                ];
                $classes = $colorMap[$this->badgeColor] ?? $colorMap['gray'];
                return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ' . $classes . '">'
                    . e($value)
                    . '</span>';
            }
        }

        return $this->rawHtml ? (string) $value : e((string) $value);
    }

    private function resolveValue(mixed $row): mixed
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }

        // Property zuerst, dann Methode
        if (isset($row->{$this->key})) {
            return $row->{$this->key};
        }

        if (method_exists($row, $this->key)) {
            return $row->{$this->key}();
        }

        return '';
    }

    // ────────────────────────────────────────────────────────────────────────
    // Getter
    // ────────────────────────────────────────────────────────────────────────

    public function getKey(): string      { return $this->key; }
    public function getLabel(): string    { return $this->label; }
    public function isSortable(): bool    { return $this->sortable; }
    public function getWidth(): ?string   { return $this->width; }
    public function getAfter(): ?string   { return $this->after; }
    public function getPriority(): int    { return $this->priority; }

    public function isVisible(): bool
    {
        if ($this->visibleWhen === null) {
            return true;
        }
        return (bool) ($this->visibleWhen)();
    }
}
