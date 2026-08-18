<?php

namespace Modules\Wareneingang\Http\Livewire;

use App\Models\Offer;
use App\Models\PositionAward;
use App\Models\User;
use Livewire\Component;
use Modules\Wareneingang\Models\GoodsReceipt;
use Modules\Wareneingang\Models\GoodsReceiptItem;
use Modules\Wareneingang\Models\GoodsReceiptParticipant;

class GoodsReceiptManager extends Component
{
    public string $projectId;

    /** cis_row_id des Offers, dessen Übersicht gerade aufgeklappt ist. */
    public ?string $expandedOfferId = null;

    /** cis_row_id des GoodsReceipt, für das gerade ein neuer Link erzeugt wird. */
    public ?string $linkFormReceiptId = null;

    public string $linkSearch = '';

    /** @var array<int, array{id: string, label: string, sub: string}> */
    public array $linkResults = [];

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function render()
    {
        $offerIds = PositionAward::where('cis_row_id_project', $this->projectId)
            ->whereNotNull('cis_row_id_offer')
            ->distinct()
            ->pluck('cis_row_id_offer');

        $offers = Offer::whereIn('cis_row_id', $offerIds)
            ->with('source')
            ->get()
            ->map(function (Offer $offer) {
                $offer->awardedCount = PositionAward::where('cis_row_id_offer', $offer->cis_row_id)->count();
                $offer->receipt      = GoodsReceipt::where('cis_row_id_offer', $offer->cis_row_id)
                    ->with(['items.position.product', 'items.lastParticipant.user', 'participants.user'])
                    ->latest('created_at')
                    ->first();
                return $offer;
            })
            ->sortBy(fn (Offer $offer) => $offer->source?->name ?? $offer->cis_row_id)
            ->values();

        return view('wareneingang::livewire.goods-receipt-manager', compact('offers'));
    }

    public function startReceipt(string $offerId): void
    {
        $exists = GoodsReceipt::where('cis_row_id_offer', $offerId)->exists();
        if ($exists) {
            return;
        }

        $receipt = GoodsReceipt::create([
            'cis_row_id_project' => $this->projectId,
            'cis_row_id_offer'   => $offerId,
        ]);

        PositionAward::where('cis_row_id_offer', $offerId)
            ->with('position')
            ->get()
            ->each(function (PositionAward $award) use ($receipt) {
                if (! $award->position) {
                    return;
                }
                GoodsReceiptItem::create([
                    'cis_row_id_goods_receipt'    => $receipt->cis_row_id,
                    'cis_row_id_project_product'  => $award->cis_row_id_project_product,
                    'expected_count'              => $award->position->product_count,
                ]);
            });

        $this->expandedOfferId = $offerId;
    }

    public function resetReceipt(string $receiptId): void
    {
        GoodsReceiptItem::where('cis_row_id_goods_receipt', $receiptId)->delete();
        GoodsReceiptParticipant::where('cis_row_id_goods_receipt', $receiptId)->delete();
        GoodsReceipt::where('cis_row_id', $receiptId)->delete();
    }

    public function openLinkForm(string $receiptId): void
    {
        $this->linkFormReceiptId = $receiptId;
        $this->linkSearch        = '';
        $this->linkResults       = [];
    }

    public function closeLinkForm(): void
    {
        $this->linkFormReceiptId = null;
        $this->linkSearch        = '';
        $this->linkResults       = [];
    }

    public function updatedLinkSearch(): void
    {
        $q = trim($this->linkSearch);

        if (mb_strlen($q) < 1) {
            $this->linkResults = [];
            return;
        }

        $this->linkResults = User::where(function ($query) use ($q) {
            $query->where('firstname', 'like', "%{$q}%")
                ->orWhere('lastname', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        })->take(8)->get()->map(fn (User $u) => [
            'id'    => (string) $u->cis_row_id,
            'label' => $u->name(),
            'sub'   => $u->email,
        ])->all();
    }

    /** Link für einen bekannten System-Benutzer erzeugen. */
    public function createParticipantForUser(string $receiptId, string $userId): void
    {
        GoodsReceiptParticipant::create([
            'cis_row_id_goods_receipt' => $receiptId,
            'cis_row_id_user'          => $userId,
            'access_token'             => GoodsReceiptParticipant::generateToken(),
        ]);

        $this->closeLinkForm();
    }

    /** Link für eine unbekannte Person (nur Freitext-Name) erzeugen. */
    public function createParticipantAsUnknown(string $receiptId): void
    {
        GoodsReceiptParticipant::create([
            'cis_row_id_goods_receipt' => $receiptId,
            'name'                     => trim($this->linkSearch) ?: null,
            'access_token'             => GoodsReceiptParticipant::generateToken(),
        ]);

        $this->closeLinkForm();
    }

    public function removeParticipant(string $participantId): void
    {
        GoodsReceiptParticipant::where('cis_row_id', $participantId)->delete();
    }

    public function toggleExpanded(string $offerId): void
    {
        $this->expandedOfferId = $this->expandedOfferId === $offerId ? null : $offerId;
    }
}
