<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class PositionAward extends Model
{
    use CisUuid;

    protected $fillable = [
        'cis_row_id_project',
        'cis_row_id_project_product',
        'cis_row_id_offer',
        'is_manual_override',
    ];

    protected $casts = [
        'is_manual_override' => 'boolean',
    ];

    public function position()
    {
        return $this->belongsTo(ProjectProduct::class, 'cis_row_id_project_product', 'cis_row_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'cis_row_id_offer', 'cis_row_id');
    }
}
