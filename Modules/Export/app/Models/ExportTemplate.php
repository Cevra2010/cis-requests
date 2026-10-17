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

    protected $fillable = ['name', 'phase', 'dataset', 'include_import_key', 'usages', 'sort_field', 'sort_direction'];

    protected $casts = [
        'include_import_key' => 'boolean',
        'usages'             => 'array',
    ];

    /** Phase, für die eine Vorlage gedacht ist – schränkt den wählbaren Feld-Pool ein (siehe ExportFieldRegistry::fieldsForPhase()). */
    public const PHASES = [
        'pre_tender'  => 'Vor der Ausschreibung',
        'post_tender' => 'Nach der Ausschreibung / Auswertung',
    ];

    /**
     * Bereiche, in denen eine Vorlage angeboten wird. Eine Vorlage erscheint nur dort,
     * wo sie ausdrücklich freigegeben ist – weitere Bereiche (z.B. ein allgemeines
     * Import-/Export-Werkzeug für Systemdaten, später REST-API) kommen hier hinzu.
     */
    public const USAGES = [
        'project_documents' => 'Projekt → Dokumentenmanager',
        'data_transfer'     => 'Import-/Export-Werkzeug',
    ];

    /** Datengrundlagen: 'project_positions' = Positionen eines Projekts (TenderExporter), sonst ein Systemdatensatz (Services/Datasets). */
    public const DATASETS = [
        'project_positions' => 'Projekt-Positionen',
        'products'          => 'Produkte',
    ];

    /** In welchen Bereichen eine Vorlage welcher Datengrundlage angeboten werden kann. */
    public const USAGE_DATASETS = [
        'project_documents' => ['project_positions'],
        'data_transfer'     => ['products'],
    ];

    public function isProjectBased(): bool
    {
        return $this->dataset === 'project_positions';
    }

    /** @return array<string, string> Bereiche (key => Label), die für die Datengrundlage dieser Vorlage in Frage kommen. */
    public function availableUsages(): array
    {
        return array_filter(
            self::USAGES,
            fn ($label, $key) => in_array($this->dataset, self::USAGE_DATASETS[$key] ?? [], true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function hasUsage(string $usage): bool
    {
        return in_array($usage, $this->usages ?? [], true);
    }

    public function columns()
    {
        return $this->hasMany(ExportTemplateColumn::class, 'cis_row_id_template', 'cis_row_id')
            ->orderBy('sort_order');
    }

    public function filters()
    {
        return $this->hasMany(ExportTemplateFilter::class, 'cis_row_id_template', 'cis_row_id')
            ->orderBy('sort_order');
    }

    /** Kann eine vom Händler ausgefüllte Datei dieser Vorlage wieder eingelesen werden (Händler-Preisfeld + Import-Schlüssel)? */
    public function isPriceImportable(): bool
    {
        return $this->include_import_key && $this->hasVendorPriceColumn();
    }

    /** Enthält die Vorlage ein Feld, das der Händler ausfüllen soll (Einzel-/Gesamtpreis)? */
    public function hasVendorPriceColumn(): bool
    {
        return $this->columns->contains(fn (ExportTemplateColumn $c) => in_array($c->field_key, ExportFieldRegistry::VENDOR_PRICE_FIELDS, true));
    }
}
