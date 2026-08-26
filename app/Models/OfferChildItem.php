<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class OfferChildItem extends Model
{
    use CisUuid;

    protected $fillable = [
        'cis_row_id_offer',
        'cis_row_id_product',
        'price',
        'not_offered',
    ];

    protected $casts = [
        'not_offered' => 'boolean',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'cis_row_id_offer', 'cis_row_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'cis_row_id_product', 'cis_row_id');
    }

    public function isValid(): bool
    {
        return ! $this->not_offered && $this->price !== null;
    }
}
