<?php

namespace Modules\Lager\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only Ereignisprotokoll für "X Stück der Wareneingangsposition Y
 * wurden in Lagerort Z eingebucht" – siehe Migration für Details. Keine
 * Relation auf GoodsReceiptItem hier (lose Referenz, Wareneingang-Modul
 * könnte deaktiviert sein) – wird bei Bedarf am Aufrufort aufgelöst, immer
 * hinter einem Module::find('Wareneingang')?->isEnabled()-Guard.
 */
class LagerPlacement extends Model
{
    use CisUuid;

    protected $table = 'lager_placements';

    protected $fillable = ['cis_row_id_goods_receipt_item', 'cis_row_id_lagerort', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function lagerort()
    {
        return $this->belongsTo(Lagerort::class, 'cis_row_id_lagerort', 'cis_row_id');
    }
}
