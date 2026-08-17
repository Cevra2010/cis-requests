<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class OfferItem extends Model
{
    use CisUuid;

    protected $fillable = [
        'cis_row_id_offer',
        'cis_row_id_project_product',
        'price',
        'not_offered',
        'note',
    ];

    protected $casts = [
        'not_offered' => 'boolean',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'cis_row_id_offer', 'cis_row_id');
    }

    public function position()
    {
        return $this->belongsTo(ProjectProduct::class, 'cis_row_id_project_product', 'cis_row_id');
    }

    public function isValid(): bool
    {
        return ! $this->not_offered && $this->price !== null;
    }
}
