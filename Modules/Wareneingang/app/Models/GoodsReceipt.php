<?php

namespace Modules\Wareneingang\Models;

use App\Models\Offer;
use App\Models\Project;
use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use CisUuid;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'cis_row_id_project',
        'cis_row_id_offer',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'cis_row_id_project', 'cis_row_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'cis_row_id_offer', 'cis_row_id');
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class, 'cis_row_id_goods_receipt', 'cis_row_id')
            ->orderBy('created_at');
    }

    public function participants()
    {
        return $this->hasMany(GoodsReceiptParticipant::class, 'cis_row_id_goods_receipt', 'cis_row_id')
            ->orderBy('created_at');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /** [abgeschlossene Positionen, Positionen gesamt] */
    public function progress(): array
    {
        $items = $this->items;
        return [$items->filter(fn (GoodsReceiptItem $i) => $i->isClosed())->count(), $items->count()];
    }

    public function hasMismatches(): bool
    {
        return $this->items->contains(fn (GoodsReceiptItem $item) => $item->isChecked() && ! $item->matchesExpected());
    }
}
