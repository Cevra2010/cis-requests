<?php

namespace App\Http\Livewire\Parameter;

use App\Models\TemplateParameter;
use CisFoundation\CisCategoryManager\CisCategoryManager;
use Livewire\Component;

class ParameterManager extends Component
{
    public const CATEGORY_TYPE = 'vehicle.spec';

    public string $search = '';

    // ── Anlegen / Bearbeiten ─────────────────────────────────────────────────
    public bool $showFormModal = false;
    public ?string $formId = null;
    public ?string $formParentId = null;
    public string $formName = '';
    public string $formDescription = '';
    public ?int $formCategoryId = null;
    public int $formSortOrder = 0;

    // ── Löschen ──────────────────────────────────────────────────────────────
    public bool $showDeleteModal = false;
    public ?string $deleteId = null;
    public string $deleteConfirmText = '';

    // ── Formular ─────────────────────────────────────────────────────────────

    public function openCreate(?string $parentId = null): void
    {
        $this->resetForm();
        $this->formParentId  = $parentId;
        $this->showFormModal = true;
    }

    public function openEdit(string $id): void
    {
        $parameter = TemplateParameter::findOrFail($id);

        $this->formId          = $parameter->cis_row_id;
        $this->formParentId    = $parameter->cis_row_id_parent;
        $this->formName        = $parameter->name;
        $this->formDescription = (string) $parameter->description;
        $this->formCategoryId  = $parameter->category_id;
        $this->formSortOrder   = $parameter->sort_order;
        $this->showFormModal   = true;
    }

    public function save(): void
    {
        $this->validate([
            'formName'        => 'required|string|max:255',
            'formDescription' => 'nullable|string|max:2000',
            'formCategoryId'  => 'nullable|integer',
            'formSortOrder'   => 'nullable|integer|min:0',
        ]);

        $data = [
            'name'               => $this->formName,
            'description'        => $this->formDescription ?: null,
            'cis_row_id_parent'  => $this->formParentId,
            'category_id'        => $this->formCategoryId,
            'sort_order'         => $this->formSortOrder,
        ];

        if ($this->formId) {
            TemplateParameter::where('cis_row_id', $this->formId)->update($data);
        } else {
            TemplateParameter::create($data);
        }

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->formId          = null;
        $this->formParentId    = null;
        $this->formName        = '';
        $this->formDescription = '';
        $this->formCategoryId  = null;
        $this->formSortOrder   = 0;
        $this->resetErrorBag();
    }

    // ── Löschen ──────────────────────────────────────────────────────────────

    public function confirmDelete(string $id): void
    {
        $this->deleteId          = $id;
        $this->deleteConfirmText = '';
        $this->showDeleteModal   = true;
    }

    public function destroy(): void
    {
        $parameter = TemplateParameter::findOrFail($this->deleteId);

        if ($this->deleteConfirmText !== 'DEL-' . $parameter->name) {
            $this->addError('deleteConfirmText', 'Sicherheitsabfrage ist nicht korrekt.');
            return;
        }

        $ids = $parameter->selfAndDescendantIds();
        TemplateParameter::whereIn('cis_row_id', $ids)->delete();

        $this->showDeleteModal   = false;
        $this->deleteId          = null;
        $this->deleteConfirmText = '';
    }

    public function render()
    {
        $query = TemplateParameter::query()->whereNull('cis_row_id_parent')->with('children');

        if (trim($this->search) !== '') {
            // Bei Suche: alle Parameter (auch Unterparameter) flach nach Name durchsuchen.
            $all = TemplateParameter::where('name', 'like', '%' . trim($this->search) . '%')
                ->orWhere('description', 'like', '%' . trim($this->search) . '%')
                ->orderBy('sort_order')->orderBy('name')->get();
        } else {
            $all = null;
        }

        $tree              = $all === null ? $query->orderBy('sort_order')->orderBy('name')->get() : null;
        $categoryOptions   = CisCategoryManager::optionsForType(self::CATEGORY_TYPE);
        $deleteParameter   = $this->deleteId ? TemplateParameter::find($this->deleteId) : null;
        $deleteDescCount   = $deleteParameter ? count($deleteParameter->selfAndDescendantIds()) - 1 : 0;

        return view('livewire.parameter.parameter-manager', compact(
            'tree', 'all', 'categoryOptions', 'deleteParameter', 'deleteDescCount'
        ));
    }
}
