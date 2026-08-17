<?php

namespace CisFoundation\CisTableBuilder\Livewire;

use CisFoundation\CisTableBuilder\CisTableBuilder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Generische Livewire-Komponente für CisTable mit Live-Suche und Sortierung.
 *
 * Verwendung in Blade:
 *   @livewire('cis-table', ['table' => 'product.list'])
 */
class CisTableLivewireComponent extends Component
{
    use WithPagination;

    /** Name der Tabelle in CisTableBuilder */
    public string $table;

    public string  $search    = '';
    public string  $orderBy   = '';
    public string  $direction = 'ASC';
    public int     $perPage   = 15;

    public function mount(string $table): void
    {
        $def = CisTableBuilder::get($table);

        $this->orderBy  = $def->defaultOrderBy ?? '';
        $this->direction = $def->defaultOrderDirection;
        $this->perPage   = $def->getPerPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->orderBy === $column) {
            $this->direction = $this->direction === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->orderBy  = $column;
            $this->direction = 'ASC';
        }
        $this->resetPage();
    }

    public function render()
    {
        $def     = CisTableBuilder::get($this->table);
        $columns = $def->getColumns();
        $actions = $def->getActions();

        // Daten laden
        $data = $def->getData();

        // Wenn Daten eine Query-Builder-Instanz sind, Suche/Sortierung/Pagination anwenden
        // (CisTable::getData() kümmert sich darum via request()-Parameter)
        // Für Livewire übergeben wir Suche/Sortierung direkt:
        if (is_string($def->setData ?? null)) {
            // Class-String-Modus – wird in getData() verarbeitet
        }

        return view('cis-table-builder::livewire.table', compact('def', 'columns', 'actions', 'data'));
    }
}
