<?php

namespace CisFoundation\CisTableBuilder;

use CisFoundation\CisHookManager\CisHooks;
use Illuminate\Support\Collection;

/**
 * CisTable – Definition einer Tabelle.
 *
 * Module erweitern bestehende Tabellen über den Hook-Bus:
 *
 *   // Im ServiceProvider des Moduls:
 *   CisHooks::addFilter('table.product.list.columns', function(Collection $columns) {
 *       $columns->push(
 *           CisColumn::make('min_quality', 'Mindestqualität')
 *               ->render(fn($row) => $row->minQuality?->label ?? '–')
 *               ->after('name')
 *       );
 *       return $columns;
 *   }, module: 'MinQualityModule');
 */
class CisTable
{
    public string $name;

    // ── Suche ──────────────────────────────────────────────────────────────
    public bool  $search       = false;
    public array $searchFields = [];

    // ── Sortierung ─────────────────────────────────────────────────────────
    public ?string $defaultOrderBy        = null;
    public string  $defaultOrderDirection = 'ASC';

    // ── Pagination ─────────────────────────────────────────────────────────
    public bool $pagination      = false;
    public int  $paginationLimit = 15;

    // ── Sonstiges ──────────────────────────────────────────────────────────
    protected ?string    $cssClass = null;
    protected mixed      $data     = null;
    protected Collection $columns;
    protected Collection $actions;
    protected bool       $useLivewire = false;

    // Row-Click: optional eine Closure die pro Zeile eine URL zurückgibt
    protected ?\Closure $rowClickUrl = null;

    public function __construct(string $name)
    {
        $this->name    = $name;
        $this->columns = collect();
        $this->actions = collect();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Columns
    // ────────────────────────────────────────────────────────────────────────

    public function addColumn(CisColumn $column): static
    {
        $this->columns->push($column);
        return $this;
    }

    /**
     * Gibt die finale, nach 'after'-Direktiven und Priority sortierte Column-Liste zurück.
     * Dabei werden Module-Hooks angewandt.
     *
     * Hook-Name: "table.{tableName}.columns"
     */
    public function getColumns(): Collection
    {
        $hookName = 'table.' . $this->name . '.columns';
        $columns  = CisHooks::applyFilter($hookName, $this->columns);

        // Sichtbarkeitsfilter
        $columns = $columns->filter(fn(CisColumn $col) => $col->isVisible());

        // 'after'-Sortierung auflösen
        $columns = $this->resolveColumnOrder($columns);

        return $columns;
    }

    /**
     * Löst 'after'-Direktiven auf und sortiert die Spalten entsprechend.
     */
    private function resolveColumnOrder(Collection $columns): Collection
    {
        // Zuerst nach Priority (Basis-Reihenfolge)
        $sorted = $columns->sortBy(fn(CisColumn $col) => $col->getPriority())->values();

        // Dann 'after'-Direktiven verarbeiten
        $reordered = collect();
        $queued    = collect(); // Spalten mit 'after' die noch nicht platziert wurden

        foreach ($sorted as $col) {
            if ($col->getAfter() !== null) {
                $queued->push($col);
                continue;
            }
            $reordered->push($col);

            // Prüfen ob wartende Spalten jetzt eingefügt werden können
            $queued = $queued->filter(function (CisColumn $waiting) use (&$reordered) {
                if ($reordered->contains(fn($c) => $c->getKey() === $waiting->getAfter())) {
                    $index = $reordered->search(fn($c) => $c->getKey() === $waiting->getAfter());
                    $slice = $reordered->splice($index + 1);
                    $reordered->push($waiting);
                    $slice->each(fn($c) => $reordered->push($c));
                    return false; // aus Queue entfernen
                }
                return true; // noch warten
            });
        }

        // Restliche (ungelöste 'after') ans Ende hängen
        $queued->each(fn($c) => $reordered->push($c));

        return $reordered;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Actions (Zeilenaktionen)
    // ────────────────────────────────────────────────────────────────────────

    public function addAction(CisTableAction $action): static
    {
        $this->actions->push($action);
        return $this;
    }

    /**
     * Gibt Actions zurück – Module können per Hook eigene hinzufügen.
     * Hook-Name: "table.{tableName}.actions"
     */
    public function getActions(): Collection
    {
        return CisHooks::applyFilter('table.' . $this->name . '.actions', $this->actions);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Row-Click URL
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Macht jede Zeile klickbar.
     * fn($row) => route('product.edit', $row)
     */
    public function rowClickUrl(\Closure $callback): static
    {
        $this->rowClickUrl = $callback;
        return $this;
    }

    public function getRowClickUrl(mixed $row): ?string
    {
        if ($this->rowClickUrl === null) {
            return null;
        }
        return ($this->rowClickUrl)($row);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Data
    // ────────────────────────────────────────────────────────────────────────

    public function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Gibt die Daten zurück.
     * Bei Class-String-Data wird Suche/Pagination automatisch angewandt.
     */
    public function getData(): mixed
    {
        if ($this->useLivewire || is_array($this->data) || $this->data instanceof Collection) {
            return $this->data;
        }
        return $this->resolveData();
    }

    private function resolveData(): mixed
    {
        $query = null;

        if ($this->search && request()->filled('search')) {
            $term = request()->get('search');
            foreach ($this->searchFields as $i => $field) {
                $query = $i === 0
                    ? ($this->data)::where($field, 'like', "%{$term}%")
                    : $query->orWhere($field, 'like', "%{$term}%");
            }
        }

        $query = $query ?? ($this->data)::query();

        if ($this->defaultOrderBy) {
            $query->orderBy($this->defaultOrderBy, $this->defaultOrderDirection);
        }

        return $this->pagination
            ? $query->paginate($this->resolvedLimit())
            : $query->get();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Konfiguration (Fluent)
    // ────────────────────────────────────────────────────────────────────────

    public function withSearch(array $searchFields = []): static
    {
        $this->search       = true;
        $this->searchFields = $searchFields;
        return $this;
    }

    public function withPagination(int $limit = 15): static
    {
        $this->pagination      = true;
        $this->paginationLimit = $limit;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->defaultOrderBy        = $column;
        $this->defaultOrderDirection = strtoupper($direction);
        return $this;
    }

    public function setCssClass(string $class): static
    {
        $this->cssClass = $class;
        return $this;
    }

    public function useLivewire(): static
    {
        $this->useLivewire = true;
        return $this;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Getter (für Livewire-Komponente und Templates)
    // ────────────────────────────────────────────────────────────────────────

    public function getCssClass(): string    { return $this->cssClass ?? 'cis-table'; }
    public function isSearchEnabled(): bool  { return $this->search; }
    public function hasPages(): bool         { return $this->pagination; }
    public function getPerPage(): int        { return $this->resolvedLimit(); }

    private function resolvedLimit(): int
    {
        return (int) (request()->get('perpage') ?: $this->paginationLimit);
    }
}
