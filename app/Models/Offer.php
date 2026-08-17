<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use CisUuid, SoftDeletes;

    protected $fillable = [
        'cis_row_id_project',
        'cis_row_id_source',
        'reference',
        'submitted_at',
        'active',
        'min_value_ignored',
        'notes',
    ];

    protected $casts = [
        'submitted_at'      => 'date',
        'active'            => 'boolean',
        'min_value_ignored' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'cis_row_id_project', 'cis_row_id');
    }

    public function source()
    {
        return $this->belongsTo(ProductSource::class, 'cis_row_id_source', 'cis_row_id');
    }

    public function items()
    {
        return $this->hasMany(OfferItem::class, 'cis_row_id_offer', 'cis_row_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Summe aus Preis × Menge über alle Positionen, die diesem Angebot AKTUELL
     * zugeordnet sind (position_awards) – nicht die volle Angebotssumme über
     * alle bepreisten Zeilen. Relevant für Mindestwert-Prüfung und Bestellliste.
     */
    public function total(): float
    {
        return (float) PositionAward::where('position_awards.cis_row_id_offer', $this->cis_row_id)
            ->join('project_product', 'position_awards.cis_row_id_project_product', '=', 'project_product.cis_row_id')
            ->join('offer_items', function ($join) {
                $join->on('offer_items.cis_row_id_project_product', '=', 'project_product.cis_row_id')
                    ->where('offer_items.cis_row_id_offer', $this->cis_row_id);
            })
            ->selectRaw('COALESCE(SUM(offer_items.price * project_product.product_count), 0) as total')
            ->value('total');
    }
}
