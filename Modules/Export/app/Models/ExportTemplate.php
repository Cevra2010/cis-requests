<?php

namespace Modules\Export\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportTemplate extends Model
{
    use CisUuid, SoftDeletes;

    protected $table = 'export_templates';

    protected $fillable = ['name', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function columns()
    {
        return $this->hasMany(ExportTemplateColumn::class, 'cis_row_id_template', 'cis_row_id')
            ->orderBy('sort_order');
    }
}
