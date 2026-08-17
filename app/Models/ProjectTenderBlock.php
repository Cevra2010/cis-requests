<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ProjectTenderBlock extends Model
{
    use CisUuid;

    protected $fillable = ['cis_row_id_project', 'type', 'sort_order', 'config'];

    protected $casts = [
        'config' => 'array',
    ];
}
