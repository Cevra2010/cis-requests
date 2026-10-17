<?php

namespace Modules\Export\Http\Livewire;

use Livewire\Component;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Models\ExportTemplateColumn;
use Modules\Export\Models\ExportTemplateFilter;
use Modules\Export\Services\ExportFieldRegistry;
use Modules\Export\Services\ExportFilterRegistry;

class TemplateManager extends Component
{
    /** @var array<string, string> Name der neuen Vorlage je Phase – getrennt, damit sich die Eingabefelder der Phasen nicht gegenseitig füllen. */
    public array $newTemplateNames = [];

    /** @var array<string, string> Name der neuen Vorlage je Systemdatensatz. */
    public array $newDatasetTemplateNames = [];

    public ?string $expandedTemplateId = null;

    public string $newColumnLabel = '';

    public string $newColumnField = '';

    public string $newColumnStaticValue = '';

    public string $newFilterField = '';

    /** @var array<int, string> */
    public array $newFilterValues = [];

    public function render()
    {
        $templates = ExportTemplate::with(['columns', 'filters'])
            ->orderBy('name')
            ->get();

        // Auswahloptionen je Filtertyp einmal je Request geladen (Kategorien/Quellen ändern
        // sich nicht innerhalb eines Renders) – sowohl für die Anzeige bestehender Filter
        // (Werte → Label) als auch für das "Filter hinzufügen"-Formular.
        $filterOptions = collect(ExportFilterRegistry::FILTERS)->keys()
            ->mapWithKeys(fn ($key) => [$key => ExportFilterRegistry::optionsFor($key)]);

        return view('export::livewire.template-manager', [
            'templatesByPhase'   => $templates->where('dataset', 'project_positions')->groupBy('phase'),
            'templatesByDataset' => $templates->where('dataset', '!=', 'project_positions')->groupBy('dataset'),
            'datasets'           => collect(ExportTemplate::DATASETS)->except('project_positions')->all(),
            'phases'            => ExportTemplate::PHASES,
            'usageOptions'      => ExportTemplate::USAGES,
            'filterFields'      => collect(ExportFilterRegistry::FILTERS)->map(fn ($f) => $f['label'])->all(),
            'filterTypes'       => collect(ExportFilterRegistry::FILTERS)->map(fn ($f) => $f['type'])->all(),
            'filterOptions'     => $filterOptions,
            'newFilterOptions'  => $this->newFilterField !== '' ? ($filterOptions[$this->newFilterField] ?? []) : [],
            'sortFields'        => ExportFieldRegistry::SORTABLE_FIELDS,
            'fieldOptions'      => collect(ExportTemplate::PHASES)->keys()
                ->mapWithKeys(fn ($phase) => [$phase => ExportFieldRegistry::optionsForPhase($phase)])
                ->merge(collect(ExportTemplate::DATASETS)->except('project_positions')->keys()
                    ->mapWithKeys(fn ($d) => [$d => ExportFieldRegistry::optionsForDataset($d)]))->all(),
            'fieldOrigins'      => collect(ExportFieldRegistry::ORIGINS)
                ->prepend(ExportFieldRegistry::FREE_FIELD_ORIGIN_LABEL, ExportFieldRegistry::FREE_FIELD_ORIGIN)->all(),
        ]);
    }

    public function createTemplate(string $phase): void
    {
        if (! array_key_exists($phase, ExportTemplate::PHASES)) {
            $phase = 'pre_tender';
        }

        $this->validate(
            ["newTemplateNames.{$phase}" => 'required|string|max:255'],
            [],
            ["newTemplateNames.{$phase}" => 'Name der Vorlage']
        );

        $template = ExportTemplate::create([
            'name'  => trim($this->newTemplateNames[$phase]),
            'phase'  => $phase,
            'usages' => ['project_documents'],
        ]);

        $this->newTemplateNames[$phase] = '';
        $this->expandedTemplateId = $template->cis_row_id;
    }

    public function createDatasetTemplate(string $dataset): void
    {
        if (! array_key_exists($dataset, ExportTemplate::DATASETS) || $dataset === 'project_positions') {
            return;
        }

        $this->validate(
            ["newDatasetTemplateNames.{$dataset}" => 'required|string|max:255'],
            [],
            ["newDatasetTemplateNames.{$dataset}" => 'Name der Vorlage']
        );

        $template = ExportTemplate::create([
            'name'               => trim($this->newDatasetTemplateNames[$dataset]),
            'phase'              => 'pre_tender',
            'dataset'            => $dataset,
            'include_import_key' => true,
            'usages'             => ['data_transfer'],
        ]);

        $this->newDatasetTemplateNames[$dataset] = '';
        $this->expandedTemplateId                = $template->cis_row_id;
    }

    public function renameTemplate(string $id, string $name): void
    {
        if (trim($name) === '') {
            return;
        }

        ExportTemplate::where('cis_row_id', $id)->update(['name' => trim($name)]);
    }

    public function setUsage(string $id, string $usage, bool $enabled): void
    {
        $template = ExportTemplate::find($id);
        if (! $template || ! array_key_exists($usage, $template->availableUsages())) {
            return;
        }

        $usages = collect($template->usages ?? [])->reject(fn ($u) => $u === $usage);
        if ($enabled) {
            $usages->push($usage);
        }

        $template->update(['usages' => $usages->unique()->values()->all()]);
    }

    /** Schreibt der Export den technischen Import-Schlüssel (Spalte "_ImportKey") als letzte Spalte mit in die Tabelle? */
    public function setImportKey(string $id, bool $enabled): void
    {
        ExportTemplate::where('cis_row_id', $id)->update(['include_import_key' => $enabled]);
    }

    public function deleteTemplate(string $id): void
    {
        ExportTemplateColumn::where('cis_row_id_template', $id)->delete();
        ExportTemplateFilter::where('cis_row_id_template', $id)->delete();
        ExportTemplate::where('cis_row_id', $id)->delete();

        if ($this->expandedTemplateId === $id) {
            $this->expandedTemplateId = null;
        }
    }

    public function toggleExpanded(string $id): void
    {
        $this->expandedTemplateId   = $this->expandedTemplateId === $id ? null : $id;
        $this->newColumnLabel       = '';
        $this->newColumnField       = '';
        $this->newColumnStaticValue = '';
        $this->newFilterField       = '';
        $this->newFilterValues      = [];
        $this->resetErrorBag();
    }

    public function addColumn(string $templateId): void
    {
        $this->validate([
            'newColumnLabel' => 'required|string|max:255',
            'newColumnField' => 'required|string',
        ]);

        $isFreeField = $this->newColumnField === ExportFieldRegistry::FREE_FIELD_KEY;
        $template    = ExportTemplate::find($templateId);

        $allowedFields = ! $template ? [] : ($template->isProjectBased()
            ? ExportFieldRegistry::fieldsForPhase($template->phase)
            : collect(ExportFieldRegistry::fieldsOf($template->dataset))->map(fn ($f) => $f['label'])->all());

        if (! $isFreeField && ! array_key_exists($this->newColumnField, $allowedFields)) {
            $this->addError('newColumnField', 'Ungültiges Feld.');
            return;
        }

        $maxOrder = ExportTemplateColumn::where('cis_row_id_template', $templateId)->max('sort_order') ?? 0;

        ExportTemplateColumn::create([
            'cis_row_id_template' => $templateId,
            'label'               => $this->newColumnLabel,
            'field_key'           => $this->newColumnField,
            'static_value'        => $isFreeField ? ($this->newColumnStaticValue ?: null) : null,
            'sort_order'          => $maxOrder + 1,
        ]);

        $this->newColumnLabel       = '';
        $this->newColumnField       = '';
        $this->newColumnStaticValue = '';
    }

    public function removeColumn(string $columnId): void
    {
        ExportTemplateColumn::where('cis_row_id', $columnId)->delete();
    }

    public function moveColumn(string $columnId, string $direction): void
    {
        $column = ExportTemplateColumn::find($columnId);
        if (! $column) {
            return;
        }

        $columns = ExportTemplateColumn::where('cis_row_id_template', $column->cis_row_id_template)
            ->orderBy('sort_order')
            ->get();

        $index = $columns->search(fn (ExportTemplateColumn $c) => $c->cis_row_id === $columnId);
        if ($index === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($targetIndex < 0 || $targetIndex >= $columns->count()) {
            return;
        }

        $current = $columns[$index];
        $target  = $columns[$targetIndex];

        ExportTemplateColumn::where('cis_row_id', $current->cis_row_id)->update(['sort_order' => $target->sort_order]);
        ExportTemplateColumn::where('cis_row_id', $target->cis_row_id)->update(['sort_order' => $current->sort_order]);
    }

    public function updatedNewFilterField(): void
    {
        $this->newFilterValues = [];
    }

    /** Frei kombinierbare (UND-verknüpfte) Filter – siehe ExportFilterRegistry/ExportTemplateFilterMatcher. */
    public function addFilter(string $templateId): void
    {
        if ($this->newFilterField === '' || ! array_key_exists($this->newFilterField, ExportFilterRegistry::FILTERS)) {
            $this->addError('newFilterField', 'Bitte ein Filterfeld wählen.');
            return;
        }

        $values = array_values(array_filter($this->newFilterValues, fn ($v) => $v !== '' && $v !== null));
        if (empty($values)) {
            $this->addError('newFilterValues', 'Bitte mindestens einen Wert wählen.');
            return;
        }

        $maxOrder = ExportTemplateFilter::where('cis_row_id_template', $templateId)->max('sort_order') ?? 0;

        ExportTemplateFilter::create([
            'cis_row_id_template' => $templateId,
            'field_key'           => $this->newFilterField,
            'value'               => $values,
            'sort_order'          => $maxOrder + 1,
        ]);

        $this->newFilterField  = '';
        $this->newFilterValues = [];
    }

    public function removeFilter(string $filterId): void
    {
        ExportTemplateFilter::where('cis_row_id', $filterId)->delete();
    }

    public function setSort(string $templateId, ?string $field, string $direction): void
    {
        ExportTemplate::where('cis_row_id', $templateId)->update([
            'sort_field'     => $field ?: null,
            'sort_direction' => $direction === 'desc' ? 'desc' : 'asc',
        ]);
    }
}
