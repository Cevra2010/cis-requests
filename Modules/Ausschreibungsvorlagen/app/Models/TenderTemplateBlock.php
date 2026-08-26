<?php

namespace Modules\Ausschreibungsvorlagen\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class TenderTemplateBlock extends Model
{
    use CisUuid;

    protected $fillable = ['cis_row_id_template', 'type', 'sort_order', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(TenderTemplate::class, 'cis_row_id_template', 'cis_row_id');
    }
}
