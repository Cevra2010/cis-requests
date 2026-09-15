<?php

namespace Modules\Lager\Http\Livewire;

use App\Http\Livewire\Concerns\HasFilterableTable;
use Livewire\Component;
use Modules\Lager\Models\Lagerort;
use Modules\Lager\Models\LagerStock;
use Modules\Lager\Services\LagerStockService;

/**
 * Warenübersicht: Bestand je Produkt/Lagerort/Projekt, mit der App-weit
 * einheitlichen Tabelle (Sortierung, Spalten-Filter, Pagination – siehe
 * HasFilterableTable), sowie Einzel- und Block-Verschieben zwischen
 * Lagerorten und "Freigeben" (Projekt-Zuordnung einer Bestandszeile
 * aufheben). Ohne $projectId die globale Übersicht (eigener Menüpunkt
 * "Lager"); mit $projectId auf ein Projekt eingeschränkt und dort in den
 * Projekt-Tab "Lager" eingebettet (siehe ProjectLagerManager).
 */
class LagerUebersicht extends Component
{
    use HasFilterableTable;

    public ?string $projectId = null;

    /** @var array<int, string> ausgewählte lager_stock-IDs für die Block-Aktion. */
    public array $selected = [];

    public bool $showMoveModal = false;
    public string $moveTargetId = '';

    public function mount(?string $projectId = null): void
    {
        $this->projectId = $projectId;
    }

    public function tableKey(): string
    {
        return $this->projectId ? "lager_stock_project_{$this->projectId}" : 'lager_stock';
    }

    public function baseQuery()
    {
        return LagerStock::query()
            ->with(['product', 'lagerort', 'project'])
            ->where('quantity', '>', 0)
            ->when($this->projectId, fn ($q) => $q->where('cis_row_id_project', $this->projectId));
    }

    public function columns(): array
    {
        $columns = [
            [
                'key' => 'product', 'label' => 'Produkt',
                'sortable' => true, 'filterable' => true,
                'value' => fn (LagerStock $s) => $s->product?->name,
            ],
            [
                'key' => 'lagerort', 'label' => 'Lagerort',
                'sortable' => true, 'filterable' => true,
                'value' => fn (LagerStock $s) => $s->lagerort?->path(),
            ],
        ];

        if (! $this->projectId) {
            $columns[] = [
                'key' => 'project', 'label' => 'Projekt',
                'sortable' => true, 'filterable' => true,
                'value' => fn (LagerStock $s) => $s->project?->name ?? '- Nicht zugeordnet -',
            ];
        }

        $columns[] = [
            'key' => 'quantity', 'label' => 'Menge',
            'sortable' => true, 'filterable' => false,
            'value' => fn (LagerStock $s) => $s->quantity,
        ];

        return $columns;
    }

    /** Setzt den Lagerort-Filter per QR-Scan (Etikett scannen -> sofort sehen, was dort liegt). */
    public function filterByLagerort(string $lagerortId): void
    {
        $lagerort = Lagerort::find($lagerortId);
        if (! $lagerort) {
            return;
        }

        $this->filters['lagerort'] = [$lagerort->path()];
        $this->resetPage();
        $this->persist('filters', $this->filters);
    }

    public function toggleSelected(string $stockId): void
    {
        if (in_array($stockId, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$stockId]));
        } else {
            $this->selected[] = $stockId;
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function openMoveModal(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $this->moveTargetId  = '';
        $this->showMoveModal = true;
    }

    public function confirmMove(LagerStockService $service): void
    {
        if ($this->moveTargetId === '') {
            $this->addError('moveTargetId', 'Bitte einen Ziel-Lagerort wählen.');
            return;
        }

        foreach ($this->selected as $stockId) {
            $row = LagerStock::find($stockId);
            if ($row) {
                $service->move($stockId, $this->moveTargetId, $row->quantity);
            }
        }

        $this->selected      = [];
        $this->showMoveModal = false;
        $this->moveTargetId  = '';
    }

    public function release(string $stockId, LagerStockService $service): void
    {
        $service->release($stockId);
    }

    public function render()
    {
        return view('lager::livewire.lager-uebersicht', [
            'rows'      => $this->paginatedRows(),
            'lagerorte' => Lagerort::flatTree(),
        ]);
    }
}
