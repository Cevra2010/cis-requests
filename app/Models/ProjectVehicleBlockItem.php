<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ProjectVehicleBlockItem extends Model
{
    use CisUuid;

    protected $table = 'project_vehicle_block_items';

    protected $fillable = [
        'cis_row_id_block',
        'type',
        'text',
        'cis_row_id_parameter',
        'source_label',
        'sort_order',
    ];

    public function block()
    {
        return $this->belongsTo(ProjectVehicleBlock::class, 'cis_row_id_block', 'cis_row_id');
    }

    public function parameter()
    {
        return $this->belongsTo(TemplateParameter::class, 'cis_row_id_parameter', 'cis_row_id');
    }
}
