<?php

namespace Modules\Export\Http\Livewire;

use Livewire\Component;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Models\ExportTemplateColumn;
use Modules\Export\Services\ExportFieldRegistry;

class TemplateManager extends Component
{
    public string $newTemplateName = '';

    public ?string $expandedTemplateId = null;

    public string $newColumnLabel = '';

    public string $newColumnField = '';

    public function render()
    {
        return view('export::livewire.template-manager', [
            'templates' => ExportTemplate::with('columns')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'fields' => ExportFieldRegistry::FIELDS,
        ]);
    }

    public function createTemplate(): void
    {
        $this->validate(['newTemplateName' => 'required|string|max:255']);

        $template = ExportTemplate::create([
            'name'       => $this->newTemplateName,
            'is_default' => ExportTemplate::count() === 0,
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
        ExportTemplate::where('cis_row_id', $id)->delete();

        if ($this->expandedTemplateId === $id) {
            $this->expandedTemplateId = null;
        }
    }

    public function toggleExpanded(string $id): void
    {
        $this->expandedTemplateId = $this->expandedTemplateId === $id ? null : $id;
        $this->newColumnLabel     = '';
        $this->newColumnField     = '';
        $this->resetErrorBag();
    }

    public function addColumn(string $templateId): void
    {
        $this->validate([
            'newColumnLabel' => 'required|string|max:255',
            'newColumnField' => 'required|string',
        ]);

        if (! array_key_exists($this->newColumnField, ExportFieldRegistry::FIELDS)) {
            $this->addError('newColumnField', 'Ungültiges Feld.');
            return;
        }

        $maxOrder = ExportTemplateColumn::where('cis_row_id_template', $templateId)->max('sort_order') ?? 0;

        ExportTemplateColumn::create([
            'cis_row_id_template' => $templateId,
            'label'               => $this->newColumnLabel,
            'field_key'           => $this->newColumnField,
            'sort_order'          => $maxOrder + 1,
        ]);

        $this->newColumnLabel = '';
        $this->newColumnField = '';
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
}
