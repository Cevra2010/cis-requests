<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSource extends Model
{
    use HasFactory, CisUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'url',
        'tender_relevant',
        'address_street',
        'address_postal_code',
        'address_city',
        'address_country',
        'contact_name',
        'contact_email',
        'contact_phone',
        'notes',
    ];

    protected $casts = [
        'tender_relevant' => 'boolean',
    ];

    public function prices()
    {
        return $this->hasMany(Price::class, 'cis_row_id_source', 'cis_row_id');
    }

    /** Produkte, die fest dieser Quelle zugeordnet sind (siehe Product::cis_row_id_source). */
    public function fixedProducts()
    {
        return $this->hasMany(Product::class, 'cis_row_id_source', 'cis_row_id');
    }
}
