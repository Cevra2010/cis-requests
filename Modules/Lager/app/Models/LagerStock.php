<?php

namespace Modules\Lager\Models;

use App\Models\Product;
use App\Models\Project;
use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Aktueller Bestand (Ist-Zustand, mutable) je Produkt/Lagerort/Projekt.
 * Wird ausschließlich über Modules\Lager\Services\LagerStockService verändert,
 * damit nie doppelte Zeilen für dieselbe Kombination entstehen (siehe dort).
 */
class LagerStock extends Model
{
    use CisUuid;

    protected $table = 'lager_stock';

    protected $fillable = ['cis_row_id_product', 'cis_row_id_lagerort', 'cis_row_id_project', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'cis_row_id_product', 'cis_row_id');
    }

    public function lagerort()
    {
        return $this->belongsTo(Lagerort::class, 'cis_row_id_lagerort', 'cis_row_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'cis_row_id_project', 'cis_row_id');
    }

    /** Nicht einem Projekt zugeordnet – frei verfügbarer Bestand. */
    public function isUnassigned(): bool
    {
        return $this->cis_row_id_project === null;
    }
}
