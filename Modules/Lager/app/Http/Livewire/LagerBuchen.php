<?php

namespace Modules\Lager\Http\Livewire;

use Livewire\Component;
use Modules\Lager\Models\Lagerort;
use Modules\Lager\Services\LagerStockService;
use Modules\Wareneingang\Models\GoodsReceiptItem;
use Nwidart\Modules\Facades\Module;

/**
 * "Ware buchen" – Weg B aus der Planung: erst Lagerort wählen (oder direkt per
 * Link/QR anspringen), dann alle über alle Wareneingänge hinweg noch nicht
 * vollständig eingelagerten Positionen in diesen Lagerort einbuchen. Braucht
 * das Wareneingang-Modul (dort kommen die zu verteilenden Positionen her) –
 * ohne das Modul zeigt die Seite nur einen entsprechenden Hinweis.
 */
class LagerBuchen extends Component
{
    public ?string $lagerortId = null;

    /** @var array<string, int> cis_row_id_goods_receipt_item => gewählte Menge */
    public array $qty = [];

    public string $search = '';

    public function mount(?string $lagerortId = null): void
    {
        $this->lagerortId = $lagerortId;
    }

    public function selectLagerort(string $lagerortId): void
    {
        $this->lagerortId = $lagerortId;
        $this->qty        = [];
    }

    public function book(string $itemId, LagerStockService $service): void
    {
        if (! $this->lagerortId) {
            return;
        }

        $item = GoodsReceiptItem::find($itemId);
        if (! $item) {
            return;
        }

        $remaining = $item->received_count - $service->placedQuantity($item->cis_row_id);
        $requested = (int) ($this->qty[$itemId] ?? $remaining);
        $quantity  = max(0, min($requested, $remaining));

        if ($quantity <= 0) {
            return;
        }

        $lagerort = Lagerort::find($this->lagerortId);
        if (! $lagerort) {
            return;
        }

        $service->receiveIntoStock($item, $lagerort, $quantity);
        unset($this->qty[$itemId]);
    }

    public function render()
    {
        $wareneingangEnabled = Module::find('Wareneingang')?->isEnabled() ?? false;
        $lagerorte           = Lagerort::flatTree();
        $lagerort            = $this->lagerortId ? Lagerort::find($this->lagerortId) : null;
        $openItems           = collect();

        if ($wareneingangEnabled && $lagerort) {
            $service = app(LagerStockService::class);

            $openItems = GoodsReceiptItem::with(['position.product', 'receipt.project', 'receipt.offer.source', 'receipt.source'])
                ->whereNotNull('received_count')
                ->get()
                ->filter(fn (GoodsReceiptItem $i) => $i->isClosed())
                ->map(function (GoodsReceiptItem $i) use ($service) {
                    $i->remaining = $i->received_count - $service->placedQuantity($i->cis_row_id);
                    return $i;
                })
                ->filter(fn (GoodsReceiptItem $i) => $i->remaining > 0)
                ->when(trim($this->search) !== '', fn ($items) => $items->filter(
                    fn (GoodsReceiptItem $i) => str_contains(mb_strtolower($i->position?->product?->name ?? ''), mb_strtolower(trim($this->search)))
                ))
                ->sortBy(fn (GoodsReceiptItem $i) => $i->position?->product?->name)
                ->values();
        }

        return view('lager::livewire.lager-buchen', compact('wareneingangEnabled', 'lagerorte', 'lagerort', 'openItems'));
    }
}
