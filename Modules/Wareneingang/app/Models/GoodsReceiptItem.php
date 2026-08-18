<?php

namespace Modules\Wareneingang\Models;

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
        'note',
        'checked_at',
    ];

    protected $casts = [
        'expected_count' => 'integer',
        'received_count' => 'integer',
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
