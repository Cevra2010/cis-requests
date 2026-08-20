<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ProjectVehicleBlock extends Model
{
    use CisUuid;

    protected $table = 'project_vehicle_blocks';

    protected $fillable = [
        'cis_row_id_project',
        'title',
        'sort_order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'cis_row_id_project', 'cis_row_id');
    }

    public function items()
    {
        return $this->hasMany(ProjectVehicleBlockItem::class, 'cis_row_id_block', 'cis_row_id')
            ->orderBy('sort_order');
    }
}
