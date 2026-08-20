<?php

namespace App\Http\Livewire\Category;

use App\Models\Category;
use CisFoundation\CisCategoryManager\CisCategoryManager;
use Livewire\Component;

class CategoryManager extends Component
{
    public string $activeType = '';

    // ── Anlegen / Bearbeiten ─────────────────────────────────────────────────
    public bool $showFormModal = false;
    public ?int $formId = null;
    public ?int $formParentId = null;
    public string $formName = '';
    public string $formDescription = '';
    public string $formColor = '#3B82F6';
    public int $formSortOrder = 0;

    // ── Löschen ──────────────────────────────────────────────────────────────
    public bool $showDeleteModal = false;
    public ?int $deleteId = null;
    public string $deleteConfirmText = '';

    public function mount(): void
    {
        $requested = request('type');

        $this->activeType = ($requested && CisCategoryManager::hasType($requested))
            ? $requested
            : (array_key_first(CisCategoryManager::getTypes()) ?? 'project.category');
    }

    public function setType(string $type): void
    {
        $this->activeType = $type;
    }

    // ── Formular ─────────────────────────────────────────────────────────────

    public function openCreate(?int $parentId = null): void
    {
        $this->resetForm();
        $this->formParentId  = $parentId;
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->formId          = $category->id;
        $this->formParentId    = $category->parent_id;
        $this->formName        = $category->name;
        $this->formDescription = (string) $category->description;
        $this->formColor       = $category->color ?: '#3B82F6';
        $this->formSortOrder   = $category->sort_order;
        $this->showFormModal   = true;
    }

    public function save(): void
    {
        $this->validate([
            'formName'        => 'required|string|max:255',
            'formDescription' => 'nullable|string|max:500',
            'formColor'       => 'nullable|string|size:7',
            'formSortOrder'   => 'nullable|integer|min:0',
        ]);

        $data = [
            'type'        => $this->activeType,
            'parent_id'   => $this->formParentId,
            'name'        => $this->formName,
            'description' => $this->formDescription ?: null,
            'color'       => $this->formColor ?: null,
            'sort_order'  => $this->formSortOrder,
            'module'      => CisCategoryManager::getTypes()[$this->activeType]['module'] ?? null,
        ];

        if ($this->formId) {
            Category::where('id', $this->formId)->update($data);
        } else {
            Category::create($data);
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
        $this->formColor       = '#3B82F6';
        $this->formSortOrder   = 0;
        $this->resetErrorBag();
    }

    // ── Löschen ──────────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->deleteId          = $id;
        $this->deleteConfirmText = '';
        $this->showDeleteModal   = true;
    }

    public function destroy(): void
    {
        $category = Category::findOrFail($this->deleteId);

        if ($this->deleteConfirmText !== 'DEL-' . $category->name) {
            $this->addError('deleteConfirmText', 'Sicherheitsabfrage ist nicht korrekt.');
            return;
        }

        $ids = $category->selfAndDescendantIds();
        Category::whereIn('id', $ids)->delete();

        $this->showDeleteModal = false;
        $this->deleteId        = null;
        $this->deleteConfirmText = '';
    }

    public function render()
    {
        $types           = CisCategoryManager::getTypes();
        $tree            = CisCategoryManager::treeForType($this->activeType);
        $deleteCategory  = $this->deleteId ? Category::find($this->deleteId) : null;
        $deleteDescCount = $deleteCategory ? count($deleteCategory->selfAndDescendantIds()) - 1 : 0;

        return view('livewire.category.category-manager', compact('types', 'tree', 'deleteCategory', 'deleteDescCount'));
    }
}
