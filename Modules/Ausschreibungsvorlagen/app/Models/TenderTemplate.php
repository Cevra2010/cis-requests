<?php

namespace Modules\Ausschreibungsvorlagen\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenderTemplate extends Model
{
    use CisUuid, SoftDeletes;

    protected $fillable = ['name', 'description'];

    public function blocks()
    {
        return $this->hasMany(TenderTemplateBlock::class, 'cis_row_id_template', 'cis_row_id')
            ->orderBy('sort_order');
    }
}
