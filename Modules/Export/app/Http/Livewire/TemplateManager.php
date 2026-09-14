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
    public string $newTemplateName = '';

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
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        // Auswahloptionen je Filtertyp einmal je Request geladen (Kategorien/Quellen ändern
        // sich nicht innerhalb eines Renders) – sowohl für die Anzeige bestehender Filter
        // (Werte → Label) als auch für das "Filter hinzufügen"-Formular.
        $filterOptions = collect(ExportFilterRegistry::FILTERS)->keys()
            ->mapWithKeys(fn ($key) => [$key => ExportFilterRegistry::optionsFor($key)]);

        return view('export::livewire.template-manager', [
            'templatesByPhase'  => $templates->groupBy('phase'),
            'phases'            => ExportTemplate::PHASES,
            'filterFields'      => collect(ExportFilterRegistry::FILTERS)->map(fn ($f) => $f['label'])->all(),
            'filterTypes'       => collect(ExportFilterRegistry::FILTERS)->map(fn ($f) => $f['type'])->all(),
            'filterOptions'     => $filterOptions,
            'newFilterOptions'  => $this->newFilterField !== '' ? ($filterOptions[$this->newFilterField] ?? []) : [],
            'sortFields'        => ExportFieldRegistry::SORTABLE_FIELDS,
        ]);
    }

    public function createTemplate(string $phase): void
    {
        $this->validate(['newTemplateName' => 'required|string|max:255']);

        if (! array_key_exists($phase, ExportTemplate::PHASES)) {
            $phase = 'pre_tender';
        }

        $template = ExportTemplate::create([
            'name'       => $this->newTemplateName,
            'is_default' => ExportTemplate::count() === 0,
            'phase'      => $phase,
        ]);

        $this->newTemplateName    = '';
        $this->expandedTemplateId = $template->cis_row_id;
    }

    public function renameTemplate(string $id, string $name): void
    {
        if (trim($name) === '') {
            return;
        }

        ExportTemplate::where('cis_row_id', $id)->update(['name' => trim($name)]);
    }

    public function setDefault(string $id): void
    {
        ExportTemplate::query()->update(['is_default' => false]);
        ExportTemplate::where('cis_row_id', $id)->update(['is_default' => true]);
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

        $isFreeField = $this->newColumnField === 'static_text';
        $template    = ExportTemplate::find($templateId);

        if (! $isFreeField && (! $template || ! array_key_exists($this->newColumnField, ExportFieldRegistry::fieldsForPhase($template->phase)))) {
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
