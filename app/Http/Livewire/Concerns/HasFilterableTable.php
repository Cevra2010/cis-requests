<?php

namespace App\Http\Livewire\Concerns;

use App\Models\UserPreference;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

/**
 * Gemeinsame Grundlage für alle "klassischen" Datentabellen (Projekte,
 * Produkte, Produktquellen, Benutzer, Gruppen, ...): Excel-artige Spalten-
 * Filter (Mehrfachauswahl aus den tatsächlich vorkommenden Werten, mit
 * Sucheingabe im Popover), Sortierung und Pagination – alles über die URL
 * teilbar und zusätzlich pro Benutzer/Tabelle in {@see UserPreference}
 * gespeichert (wird geladen, wenn die URL keinen abweichenden Wert vorgibt).
 *
 * Eine Komponente, die dieses Trait nutzt, muss nur noch drei Dinge liefern:
 * - tableKey(): eindeutiger Schlüssel für die gespeicherten Einstellungen
 * - baseQuery(): die Grund-Query (inkl. Eager-Loads), vor jedem Filter/Sort
 * - columns(): die Spaltendefinitionen (siehe Doku dort)
 *
 * Filter-/Sortier-/Options-Logik läuft bewusst einheitlich über eine
 * Value-Closure je Spalte statt über spaltentyp-abhängige SQL-Bausteine –
 * das hält jede einzelne Tabellen-Komponente klein und funktioniert
 * gleichermaßen für reine DB-Spalten wie für berechnete Anzeigewerte
 * (Preise, Status-Badges, Mehrfach-Zuordnungen wie Gruppen/Rollen).
 */
trait HasFilterableTable
{
    use WithPagination;

    /** @var array<string, array<int, string>> Spalten-Key => gewählte Werte (als String). */
    public array $filters = [];

    public string $orderBy = '';
    public string $orderDirection = '';
    public int $perPage = 0;

    protected ?Collection $baseRowsCache = null;

    abstract public function tableKey(): string;

    /** @return \Illuminate\Database\Eloquent\Builder */
    abstract public function baseQuery();

    /**
     * @return array<int, array{
     *     key: string, label: string,
     *     sortable?: bool, filterable?: bool,
     *     column?: string,
     *     value?: \Closure, sortValue?: \Closure, format?: \Closure, optionsSource?: \Closure,
     * }>
     *
     * `value` liefert den/die Rohwert(e) einer Zeile für diese Spalte (Array bei
     * Mehrfachzuordnungen wie Gruppen/Rollen) – genutzt für Filter-Abgleich,
     * Options-Liste und, falls `sortValue` fehlt, auch für die Sortierung.
     * `sortValue` überschreibt nur die Sortierung, wenn der Anzeige-/Filterwert
     * (z.B. ein formatiertes Datum) nicht auch der richtige Sortierschlüssel ist.
     */
    abstract public function columns(): array;

    public function queryStringHasFilterableTable(): array
    {
        return [
            'filters'        => ['except' => []],
            'orderBy'        => ['except' => ''],
            'orderDirection' => ['except' => ''],
            'perPage'        => ['except' => 0],
        ];
    }

    public function mountHasFilterableTable(): void
    {
        $user = Auth::user();
        $prefix = "table.{$this->tableKey()}.";

        if ($this->orderBy === '') {
            $this->orderBy = ($user ? UserPreference::get($user, $prefix . 'sort_by') : null)
                ?? $this->defaultOrderBy();
        }
        if ($this->orderDirection === '') {
            $this->orderDirection = ($user ? UserPreference::get($user, $prefix . 'sort_dir') : null) ?? 'ASC';
        }
        if ($this->perPage === 0) {
            $this->perPage = (int) (($user ? UserPreference::get($user, $prefix . 'per_page') : null) ?? 50);
        }
        if ($this->filters === [] && $user) {
            $this->filters = UserPreference::get($user, $prefix . 'filters', []);
        }
    }

    protected function defaultOrderBy(): string
    {
        $first = collect($this->columns())->firstWhere('sortable', true);

        return $first['key'] ?? '';
    }

    protected function persist(string $suffix, $value): void
    {
        if ($user = Auth::user()) {
            UserPreference::set($user, "table.{$this->tableKey()}.{$suffix}", $value);
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // Actions
    // ────────────────────────────────────────────────────────────────────

    public function toggleFilterValue(string $column, $value): void
    {
        $value   = (string) $value;
        $current = $this->filters[$column] ?? [];

        if (in_array($value, $current, true)) {
            $current = array_values(array_diff($current, [$value]));
        } else {
            $current[] = $value;
        }

        if (empty($current)) {
            unset($this->filters[$column]);
        } else {
            $this->filters[$column] = $current;
        }

        $this->resetPage();
        $this->persist('filters', $this->filters);
    }

    public function clearColumnFilter(string $column): void
    {
        unset($this->filters[$column]);
        $this->resetPage();
        $this->persist('filters', $this->filters);
    }

    public function resetAllFilters(): void
    {
        $this->filters = [];
        $this->resetPage();
        $this->persist('filters', $this->filters);
    }

    public function order(string $column): void
    {
        if ($column === $this->orderBy) {
            $this->orderDirection = $this->orderDirection === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->orderBy        = $column;
            $this->orderDirection = 'ASC';
        }

        $this->persist('sort_by', $this->orderBy);
        $this->persist('sort_dir', $this->orderDirection);
    }

    public function updatedPerPage(): void
    {
        if ($this->perPage <= 0) {
            $this->perPage = 50;
        }
        $this->resetPage();
        $this->persist('per_page', $this->perPage);
    }

    // ────────────────────────────────────────────────────────────────────
    // Daten
    // ────────────────────────────────────────────────────────────────────

    /** Ungefilterte Grundmenge, für die Options-Listen der Filter-Popover – einmal je Request geladen. */
    protected function baseRows(): Collection
    {
        if ($this->baseRowsCache === null) {
            $this->baseRowsCache = $this->baseQuery()->get();
        }

        return $this->baseRowsCache;
    }

    /**
     * Distinkte, aktuell vorkommende Werte einer Spalte fürs Filter-Popover
     * (Wert => Anzeigelabel). Bewusst gegen die volle, ungefilterte
     * Grundmenge berechnet – kein gegenseitiges Einschränken der
     * Options-Listen durch andere aktive Filter.
     */
    public function filterOptionsFor(string $key): array
    {
        $col = collect($this->columns())->firstWhere('key', $key);
        if (! $col || empty($col['filterable'])) {
            return [];
        }

        if (isset($col['optionsSource'])) {
            return ($col['optionsSource'])();
        }

        if (empty($col['value'])) {
            return [];
        }

        $format = $col['format'] ?? fn ($v) => (string) $v;
        $values = collect();

        foreach ($this->baseRows() as $row) {
            $v      = ($col['value'])($row);
            $values = $values->merge(is_array($v) ? $v : [$v]);
        }

        return $values
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->unique()
            ->sort()
            ->mapWithKeys(fn ($v) => [(string) $v => $format($v)])
            ->all();
    }

    public function paginatedRows(): LengthAwarePaginator
    {
        $columns = collect($this->columns())->keyBy('key');
        $query   = $this->baseQuery();

        // Günstige Vor-Filterung auf DB-Ebene, wo eine echte Spalte bekannt ist.
        foreach ($this->filters as $key => $values) {
            if (empty($values)) {
                continue;
            }
            $col = $columns->get($key);
            if ($col && ! empty($col['column'])) {
                $query->whereIn($col['column'], $values);
            }
        }

        $rows = $query->get();

        // Einheitliche Nachfilterung über die Value-Closure jeder Spalte
        // (deckt sowohl DB- als auch berechnete Spalten gleichermaßen ab).
        foreach ($this->filters as $key => $values) {
            if (empty($values)) {
                continue;
            }
            $col = $columns->get($key);
            if (! $col || empty($col['value'])) {
                continue;
            }

            $rows = $rows->filter(function ($row) use ($col, $values) {
                $v = ($col['value'])($row);
                $v = is_array($v) ? $v : [$v];
                $v = array_map('strval', array_filter($v, fn ($x) => $x !== null && $x !== ''));

                return count(array_intersect($v, $values)) > 0;
            })->values();
        }

        if ($this->orderBy !== '' && ($col = $columns->get($this->orderBy))) {
            $sortValue = $col['sortValue'] ?? $col['value'] ?? null;
            if ($sortValue) {
                $rows = $this->orderDirection === 'DESC'
                    ? $rows->sortByDesc($sortValue)->values()
                    : $rows->sortBy($sortValue)->values();
            }
        }

        $page    = $this->getPage();
        $perPage = $this->perPage > 0 ? $this->perPage : 50;
        $slice   = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $rows->count(),
            $perPage,
            null,
            ['pageName' => 'page']
        );
    }
}
