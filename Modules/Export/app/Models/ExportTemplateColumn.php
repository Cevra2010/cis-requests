<?php

namespace Modules\Export\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ExportTemplateColumn extends Model
{
    use CisUuid;

    protected $table = 'export_template_columns';

    protected $fillable = ['cis_row_id_template', 'label', 'field_key', 'static_value', 'sort_order'];

    /** "Freifeld": nicht an ein echtes Datenfeld gebunden (fester Text oder leer, siehe static_value). */
    public function isFreeField(): bool
    {
        return $this->field_key === 'static_text';
    }

    public function template()
    {
        return $this->belongsTo(ExportTemplate::class, 'cis_row_id_template', 'cis_row_id');
    }
}
