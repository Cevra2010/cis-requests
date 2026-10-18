<?php

namespace Modules\Export\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Export\Services\Datasets\Datasets;
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

    public const PROJECT_DATASET = 'project_positions';

    /**
     * Datengrundlagen (Schlüssel => Name): Projekt-Positionen (TenderExporter) sowie alle aktuell verfügbaren
     * Systemdatensätze (siehe Services/Datasets – z.B. Benutzer, Rollen, Projekte).
     *
     * @return array<string, string>
     */
    public static function datasets(): array
    {
        return [self::PROJECT_DATASET => 'Projekt-Positionen']
            + collect(Datasets::available())->map(fn ($d) => $d->label())->all();
    }

    public function isProjectBased(): bool
    {
        return $this->dataset === self::PROJECT_DATASET;
    }

    /** @return array<string, string> Bereiche (key => Label), in denen eine Vorlage dieser Datengrundlage angeboten werden kann. */
    public function availableUsages(): array
    {
        $usages = $this->isProjectBased() ? ['project_documents'] : ['data_transfer'];

        return array_intersect_key(self::USAGES, array_flip($usages));
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
