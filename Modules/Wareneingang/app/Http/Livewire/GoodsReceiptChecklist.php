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

    /**
     * Vorausgewählter Ziel-Lagerort für den gesamten Erfassungsvorgang (Modul Lager,
     * optional). Per Auswahl oder QR-Scan gesetzt; solange gesetzt, wird jede neu
     * erfasste Menge automatisch dorthin eingebucht – kann während des Erfassens
     * jederzeit geändert werden (z.B. neuen QR-Code scannen), falls eine Position
     * woanders hin kommt.
     */
    public string $currentLagerortId = '';

    /** Hinweis, wenn ein per Auswahl/QR-Scan gewählter Lagerort dem Projekt nicht zugeteilt ist. */
    public string $lagerortWarning = '';

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

        $receipt = $participant->receipt()->with(['project', 'offer.source', 'source'])->firstOrFail();

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

        $statusCategoryOptions = \CisFoundation\CisCategoryManager\CisCategoryManager::optionsForType('wareneingang.item_status');

        // Optionale Erweiterung durch das Lager-Modul (Weg A aus der Planung: direkt beim
        // Erfassen der Menge auch gleich den Lagerort zuweisen) – nur wenn Lager installiert
        // UND aktiv ist; ohne das Modul bleibt dieser Block komplett leer/wirkungslos. Wählbar
        // sind ausschließlich die dem Projekt zugeteilten Lagerorte (siehe Projekt → Lager).
        $lagerEnabled     = \Nwidart\Modules\Facades\Module::find('Lager')?->isEnabled() ?? false;
        $lagerorte        = collect();
        $lagerPlacedCount = [];
        $lagerPlacements  = [];

        if ($lagerEnabled) {
            $lagerorte = $receipt->project
                ? \Modules\Lager\Models\Lagerort::whereIn('cis_row_id', $this->assignedLagerortIds())->orderBy('name')->get()
                : collect();
            $service = app(\Modules\Lager\Services\LagerStockService::class);
            foreach ($allItems as $i) {
                $lagerPlacedCount[$i->cis_row_id] = $service->placedQuantity($i->cis_row_id);
                $lagerPlacements[$i->cis_row_id]  = $service->placedByLagerort($i->cis_row_id);
            }
        }

        return view('wareneingang::livewire.goods-receipt-checklist', [
            'participant'           => $participant,
            'receipt'               => $receipt,
            'items'                 => $items,
            'openCount'             => $openCount,
            'closedCount'           => $closedCount,
            'totalCount'            => $allItems->count(),
            'otherParticipants'     => $otherParticipants,
            'statusCategoryOptions' => $statusCategoryOptions,
            'lagerEnabled'          => $lagerEnabled,
            'lagerorte'             => $lagerorte,
            'lagerPlacedCount'      => $lagerPlacedCount,
            'lagerPlacements'       => $lagerPlacements,
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

    /** cis_row_id der diesem Wareneingangs-Projekt zugeteilten Lagerorte (Projekt → Lager). */
    private function assignedLagerortIds(): array
    {
        $project = $this->participant()->receipt->project;

        return $project ? $project->lagerorte()->pluck('cis_row_id')->all() : [];
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
        $item  = $this->item($itemId);
        $item->setReceived($count, $this->participant());
        $this->autoBookLagerort($item);
    }

    public function increment(string $itemId): void
    {
        $item = $this->item($itemId);
        $item->setReceived(($item->received_count ?? 0) + 1, $this->participant());
        $this->autoBookLagerort($item);
    }

    public function decrement(string $itemId): void
    {
        $item = $this->item($itemId);
        $item->setReceived(max(0, ($item->received_count ?? 0) - 1), $this->participant());
        $this->autoBookLagerort($item);
    }

    public function markFull(string $itemId): void
    {
        $item = $this->item($itemId);
        $item->setReceived($item->expected_count, $this->participant());
        $this->autoBookLagerort($item);
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

    public function updateLagerort(string $itemId, string $value): void
    {
        $item = $this->item($itemId);
        $item->update([
            'lagerort'                     => trim($value) ?: null,
            'cis_row_id_last_participant'  => $this->participant()->cis_row_id,
        ]);
    }

    /**
     * Bucht die Menge direkt in einen Lagerort des Lager-Moduls ein (Weg A aus der
     * Planung). Einziger Punkt, an dem diese Komponente auf Lager-Klassen verweist –
     * vollständig durch den Enabled-Guard abgesichert, PHP löst die referenzierten
     * Klassen erst beim tatsächlichen Ausführen dieser Zeile auf: ein deaktiviertes
     * oder fehlendes Lager-Modul verursacht daher keinen Fehler.
     */
    public function assignLagerort(string $itemId, string $lagerortId, int $quantity): void
    {
        if (! \Nwidart\Modules\Facades\Module::find('Lager')?->isEnabled()) {
            return;
        }
        if ($lagerortId === '' || $quantity <= 0) {
            return;
        }

        if (! in_array($lagerortId, $this->assignedLagerortIds(), true)) {
            $this->lagerortWarning = 'Lagerort nicht dem Projekt zugeteilt.';
            return;
        }

        $item     = $this->item($itemId);
        $lagerort = \Modules\Lager\Models\Lagerort::find($lagerortId);
        if (! $lagerort) {
            return;
        }

        $this->lagerortWarning = '';
        app(\Modules\Lager\Services\LagerStockService::class)->receiveIntoStock($item, $lagerort, $quantity);
    }

    /**
     * Setzt den Ziel-Lagerort für die Auto-Buchung (siehe autoBookLagerort()) – per
     * Auswahl oder QR-Scan. Nur die dem Projekt zugeteilten Lagerorte sind gültig; ein
     * gescannter Lagerort außerhalb dieser Zuteilung wird abgelehnt und stattdessen als
     * Hinweis angezeigt, statt ihn stillschweigend zu übernehmen.
     */
    public function setCurrentLagerort(string $lagerortId): void
    {
        $this->lagerortWarning = '';

        if ($lagerortId === '') {
            $this->currentLagerortId = '';
            return;
        }

        if (! in_array($lagerortId, $this->assignedLagerortIds(), true)) {
            $this->lagerortWarning = 'Lagerort nicht dem Projekt zugeteilt.';
            return;
        }

        $this->currentLagerortId = $lagerortId;
    }

    /**
     * Bucht die seit der letzten Buchung neu erfasste Menge automatisch in den
     * vorausgewählten Ziel-Lagerort ein (siehe $currentLagerortId) – der eigentliche
     * gewünschte Ablauf: Ziel einmal wählen/scannen, danach lädt jede erfasste Menge
     * direkt dort. Ohne gewählten Ziel-Lagerort passiert nichts (dann bleibt nur die
     * manuelle Einzel-Buchung je Position, siehe assignLagerort()).
     */
    private function autoBookLagerort(GoodsReceiptItem $item): void
    {
        if ($this->currentLagerortId === '' || ! \Nwidart\Modules\Facades\Module::find('Lager')?->isEnabled()) {
            return;
        }

        $lagerort = \Modules\Lager\Models\Lagerort::find($this->currentLagerortId);
        if (! $lagerort) {
            return;
        }

        $service   = app(\Modules\Lager\Services\LagerStockService::class);
        $remaining = ($item->received_count ?? 0) - $service->placedQuantity($item->cis_row_id);

        if ($remaining > 0) {
            $service->receiveIntoStock($item, $lagerort, $remaining);
        }
    }

    public function updateStatusCategory(string $itemId, string $categoryId): void
    {
        $item = $this->item($itemId);
        $item->update([
            'status_category_id'          => $categoryId !== '' ? $categoryId : null,
            'cis_row_id_last_participant' => $this->participant()->cis_row_id,
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
