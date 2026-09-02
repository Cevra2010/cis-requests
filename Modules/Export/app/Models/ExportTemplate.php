<?php

namespace Modules\Export\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Export\Services\ExportFieldRegistry;

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

    /** Enthält die Vorlage ein Feld, das der Händler ausfüllen soll (Einzel-/Gesamtpreis)? */
    public function hasVendorPriceColumn(): bool
    {
        return $this->columns->contains(fn (ExportTemplateColumn $c) => in_array($c->field_key, ExportFieldRegistry::VENDOR_PRICE_FIELDS, true));
    }
}
