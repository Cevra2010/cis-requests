<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ChildPositionAward extends Model
{
    use CisUuid;

    protected $fillable = [
        'cis_row_id_project',
        'cis_row_id_product',
        'cis_row_id_offer',
        'is_manual_override',
    ];

    protected $casts = [
        'is_manual_override' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'cis_row_id_product', 'cis_row_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'cis_row_id_offer', 'cis_row_id');
    }
}
