<?php

namespace Modules\Wareneingang\Models;

use App\Models\Category;
use App\Models\ProjectProduct;
use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    use CisUuid;

    protected $table = 'goods_receipt_items';

    protected $fillable = [
        'cis_row_id_goods_receipt',
        'cis_row_id_project_product',
        'cis_row_id_last_participant',
        'expected_count',
        'received_count',
        'draft_count',
        'draft_lagerort_id',
        'note',
        'checked_at',
        'lagerort',
        'status_category_id',
    ];

    protected $casts = [
        'expected_count' => 'integer',
        'received_count' => 'integer',
        'draft_count'    => 'integer',
        'checked_at'     => 'datetime',
    ];

    public function receipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'cis_row_id_goods_receipt', 'cis_row_id');
    }

    public function position()
    {
        return $this->belongsTo(ProjectProduct::class, 'cis_row_id_project_product', 'cis_row_id');
    }

    public function lastParticipant()
    {
        return $this->belongsTo(GoodsReceiptParticipant::class, 'cis_row_id_last_participant', 'cis_row_id');
    }

    /** Folgestatus nach kontrolliertem Wareneingang (z.B. "Eingelagert", "Versendet an Fahrzeughersteller"). */
    public function statusCategory()
    {
        return $this->belongsTo(Category::class, 'status_category_id', 'id');
    }

    public function isChecked(): bool
    {
        return $this->received_count !== null;
    }

    public function matchesExpected(): bool
    {
        return $this->received_count === $this->expected_count;
    }

    /**
     * Noch offen: nichts erfasst, oder es wird noch mehr erwartet (z.B. weil die
     * Lieferung sich über mehrere Tage/Teillieferungen erstreckt).
     */
    public function isOpen(): bool
    {
        return ! $this->isClosed();
    }

    /** Abgeschlossen: mindestens die erwartete Menge wurde erfasst. */
    public function isClosed(): bool
    {
        return $this->received_count !== null && $this->received_count >= $this->expected_count;
    }

    /** Erfasst, aber noch nicht per "Jetzt buchen" gebucht. */
    public function hasDraft(): bool
    {
        return $this->draft_count !== null;
    }

    /** Erfasste (noch ungebuchte) Menge deckt die erwartete Menge ab. */
    public function isDraftComplete(): bool
    {
        return $this->hasDraft() && $this->draft_count >= $this->expected_count;
    }

    /** Aktueller Erfassungsstand: ungebuchte Erfassung, sonst gebuchte Menge. */
    public function currentCount(): ?int
    {
        return $this->draft_count ?? $this->received_count;
    }

    public function setDraft(?int $count, ?GoodsReceiptParticipant $by = null, ?string $lagerortId = null): void
    {
        $this->update([
            'draft_count'                 => $count === null ? null : max(0, $count),
            'draft_lagerort_id'           => $count === null ? null : $lagerortId,
            'cis_row_id_last_participant' => $by?->cis_row_id ?? $this->cis_row_id_last_participant,
        ]);
    }

    /** Übernimmt die ungebuchte Erfassung als gebuchten Stand ("Jetzt buchen"). */
    public function commitDraft(?GoodsReceiptParticipant $by = null): void
    {
        if (! $this->hasDraft()) {
            return;
        }
        $count = $this->draft_count;
        $this->update(['draft_count' => null, 'draft_lagerort_id' => null]);
        $this->setReceived($count, $by);
    }

    public function setReceived(?int $count, ?GoodsReceiptParticipant $by = null): void
    {
        $count = $count === null ? null : max(0, $count);
        $this->update([
            'received_count'              => $count,
            'checked_at'                  => $count === null ? null : now(),
            'cis_row_id_last_participant' => $by?->cis_row_id ?? $this->cis_row_id_last_participant,
        ]);
    }
}
