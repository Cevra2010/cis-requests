<?php

namespace Modules\Lager\Http\Livewire;

use Livewire\Component;
use Modules\Lager\Models\Lagerort;
use Modules\Lager\Models\LagerStock;
use Modules\Lager\Services\LagerStockService;

/**
 * Globale Warenübersicht: Bestand je Produkt/Lagerort/Projekt, filterbar,
 * mit Einzel- und Block-Verschieben zwischen Lagerorten sowie "Freigeben"
 * (Projekt-Zuordnung einer Bestandszeile aufheben).
 */
class LagerUebersicht extends Component
{
    public string $search = '';

    public string $lagerortFilter = '';

    /** '' = alle, 'none' = nicht zugeordnet, sonst eine Projekt-cis_row_id. */
    public string $projectFilter = '';

    /** @var array<int, string> ausgewählte lager_stock-IDs für die Block-Aktion. */
    public array $selected = [];

    public bool $showMoveModal = false;
    public string $moveTargetId = '';

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
        $rows = LagerStock::with(['product', 'lagerort', 'project'])
            ->where('quantity', '>', 0)
            ->when(trim($this->search) !== '', function ($q) {
                $needle = trim($this->search);
                $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$needle}%"));
            })
            ->when($this->lagerortFilter !== '', fn ($q) => $q->where('cis_row_id_lagerort', $this->lagerortFilter))
            ->when($this->projectFilter === 'none', fn ($q) => $q->whereNull('cis_row_id_project'))
            ->when($this->projectFilter !== '' && $this->projectFilter !== 'none', fn ($q) => $q->where('cis_row_id_project', $this->projectFilter))
            ->get()
            ->sortBy(fn (LagerStock $s) => $s->product?->name)
            ->values();

        $lagerorte = Lagerort::flatTree();
        $projects  = $rows->pluck('project')->filter()->unique('cis_row_id')->sortBy('name')->values();

        return view('lager::livewire.lager-uebersicht', compact('rows', 'lagerorte', 'projects'));
    }
}
