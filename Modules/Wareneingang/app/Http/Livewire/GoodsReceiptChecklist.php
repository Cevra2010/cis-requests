<?php

namespace Modules\Wareneingang\Http\Livewire;

use Livewire\Component;
use Modules\Wareneingang\Models\GoodsReceipt;
use Modules\Wareneingang\Models\GoodsReceiptItem;
use Modules\Wareneingang\Models\GoodsReceiptParticipant;

class GoodsReceiptChecklist extends Component
{
    /**
     * Der Token identifiziert genau einen Teilnehmer (Kommissionierer). Jede
     * Aktion löst ihn ausschließlich darüber auf – nie über eine gespeicherte
     * interne ID – damit die Kommissionieransicht ohne Login sicher bleibt.
     */
    public string $token;

    public string $name = '';

    public string $search = '';

    /** offen | abgeschlossen | alle */
    public string $filter = 'offen';

    public function mount(string $token): void
    {
        $participant = GoodsReceiptParticipant::where('access_token', $token)->firstOrFail();

        $this->token = $token;
        $this->name  = $participant->name ?? '';
        $participant->touchPresence();
    }

    public function render()
    {
        $participant = $this->participant();
        $participant->touchPresence();

        $receipt = $participant->receipt()->with(['project', 'offer.source'])->firstOrFail();

        $otherParticipants = $receipt->participants()
            ->where('cis_row_id', '!=', $participant->cis_row_id)
            ->with('user')
            ->get()
            ->filter(fn (GoodsReceiptParticipant $p) => $p->isActive());

        $allItems = $receipt->items()->with(['position.product', 'lastParticipant.user'])->get();

        $openCount   = $allItems->filter(fn (GoodsReceiptItem $i) => $i->isOpen())->count();
        $closedCount = $allItems->count() - $openCount;

        $items = match ($this->filter) {
            'abgeschlossen' => $allItems->filter(fn (GoodsReceiptItem $i) => $i->isClosed()),
            'alle'          => $allItems,
            default         => $allItems->filter(fn (GoodsReceiptItem $i) => $i->isOpen()),
        };

        if (trim($this->search) !== '') {
            $needle = mb_strtolower(trim($this->search));
            $items  = $items->filter(fn (GoodsReceiptItem $i) => str_contains(
                mb_strtolower($i->position->product->name ?? ''),
                $needle
            ));
        }

        $items = $items->sortBy(fn (GoodsReceiptItem $i) => $i->position?->sort_order ?? 0)->values();

        return view('wareneingang::livewire.goods-receipt-checklist', [
            'participant'        => $participant,
            'receipt'            => $receipt,
            'items'              => $items,
            'openCount'          => $openCount,
            'closedCount'        => $closedCount,
            'totalCount'         => $allItems->count(),
            'otherParticipants'  => $otherParticipants,
        ]);
    }

    private function participant(): GoodsReceiptParticipant
    {
        return GoodsReceiptParticipant::where('access_token', $this->token)->firstOrFail();
    }

    private function item(string $itemId): GoodsReceiptItem
    {
        return GoodsReceiptItem::where('cis_row_id', $itemId)
            ->whereHas('receipt.participants', fn ($q) => $q->where('access_token', $this->token))
            ->firstOrFail();
    }

    public function updatedName(string $value): void
    {
        $participant = $this->participant();
        if ($participant->nameIsEditable()) {
            $participant->update(['name' => trim($value) ?: null]);
        }
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['offen', 'abgeschlossen', 'alle'], true) ? $filter : 'offen';
    }

    public function setReceived(string $itemId, $value): void
    {
        $count = $value === '' || $value === null ? null : max(0, (int) $value);
        $this->item($itemId)->setReceived($count, $this->participant());
    }

    public function increment(string $itemId): void
    {
        $item = $this->item($itemId);
        $item->setReceived(($item->received_count ?? 0) + 1, $this->participant());
    }

    public function decrement(string $itemId): void
    {
        $item = $this->item($itemId);
        $item->setReceived(max(0, ($item->received_count ?? 0) - 1), $this->participant());
    }

    public function markFull(string $itemId): void
    {
        $item = $this->item($itemId);
        $item->setReceived($item->expected_count, $this->participant());
    }

    public function markMissing(string $itemId): void
    {
        $item = $this->item($itemId);
        if ($item->received_count === null) {
            $item->setReceived(0, $this->participant());
        }
    }

    public function updateNote(string $itemId, string $note): void
    {
        $item = $this->item($itemId);
        $item->update([
            'note'                         => trim($note) ?: null,
            'cis_row_id_last_participant'  => $this->participant()->cis_row_id,
        ]);
    }

    public function finish(): void
    {
        $this->participant()->receipt()->update(['completed_at' => now()]);
    }

    public function reopen(): void
    {
        $this->participant()->receipt()->update(['completed_at' => null]);
    }
}
