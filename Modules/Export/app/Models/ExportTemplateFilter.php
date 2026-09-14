<?php

namespace Modules\Export\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ExportTemplateFilter extends Model
{
    use CisUuid;

    protected $table = 'export_template_filters';

    protected $fillable = ['cis_row_id_template', 'field_key', 'value', 'sort_order'];

    protected $casts = ['value' => 'array'];

    public function template()
    {
        return $this->belongsTo(ExportTemplate::class, 'cis_row_id_template', 'cis_row_id');
    }
}
