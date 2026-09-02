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

    // ── Verschieben nach... (Alternative zu Drag & Drop, nur beim Bearbeiten) ──
    public ?int $moveTargetParentId = null;
    public ?int $moveAfterId = null;

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
        $this->showFormModal   = true;

        // "Verschieben nach..." mit der aktuellen Position vorbelegen, damit ein
        // Klick auf "Verschieben" ohne weitere Auswahl nichts verändert.
        $this->moveTargetParentId = $category->parent_id;
        $siblingIds = Category::where('type', $category->type)
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();
        $ownIndex        = array_search($id, $siblingIds, true);
        $this->moveAfterId = ($ownIndex !== false && $ownIndex > 0) ? $siblingIds[$ownIndex - 1] : null;
    }

    public function save(): void
    {
        $this->validate([
            'formName'        => 'required|string|max:255',
            'formDescription' => 'nullable|string|max:500',
            'formColor'       => 'nullable|string|size:7',
        ]);

        $data = [
            'type'        => $this->activeType,
            'parent_id'   => $this->formParentId,
            'name'        => $this->formName,
            'description' => $this->formDescription ?: null,
            'color'       => $this->formColor ?: null,
            'module'      => CisCategoryManager::getTypes()[$this->activeType]['module'] ?? null,
        ];

        if ($this->formId) {
            // Reihenfolge/Elternelement werden ausschließlich über reorder() geändert.
            Category::where('id', $this->formId)->update($data);
        } else {
            $data['sort_order'] = (Category::where('type', $this->activeType)
                ->where('parent_id', $this->formParentId)
                ->max('sort_order') ?? -1) + 1;
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
        $this->formId             = null;
        $this->formParentId       = null;
        $this->formName           = '';
        $this->formDescription    = '';
        $this->formColor          = '#3B82F6';
        $this->moveTargetParentId = null;
        $this->moveAfterId        = null;
        $this->resetErrorBag();
    }

    // ── Verschieben (Drag & Drop + Formular-Alternative) ────────────────────

    /**
     * Verschiebt einen Knoten zu einem neuen Elternelement (null = oberste Ebene)
     * an eine bestimmte Position unter dessen Geschwistern. Wird sowohl vom
     * Drag & Drop (per $wire.reorder(...) aus JS) als auch von der
     * "Verschieben nach..."-Formular-Alternative genutzt.
     */
    public function reorder(int $id, ?int $newParentId, int $newIndex): void
    {
        $node = Category::findOrFail($id);

        // Schutz: nicht in sich selbst oder einen eigenen Nachfahren verschieben.
        if ($newParentId && in_array($newParentId, $node->selfAndDescendantIds(), true)) {
            return;
        }

        $node->update(['parent_id' => $newParentId]);

        $siblings = Category::where('type', $node->type)
            ->where('parent_id', $newParentId)
            ->where('id', '!=', $id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        array_splice($siblings, max(0, min($newIndex, count($siblings))), 0, [$id]);

        foreach ($siblings as $position => $siblingId) {
            Category::where('id', $siblingId)->update(['sort_order' => $position]);
        }
    }

    public function updatedMoveTargetParentId(): void
    {
        // Eine unter dem alten Ziel gewählte Position ergibt unter dem neuen keinen Sinn mehr.
        $this->moveAfterId = null;
    }

    /** Optionen für den "Übergeordnetes Element"-Select: alle anderen Knoten dieses Typs, eigene Nachfahren ausgeschlossen. */
    public function moveTargetOptions(): array
    {
        if (! $this->formId) {
            return [];
        }
        $node      = Category::find($this->formId);
        $excluded  = $node ? $node->selfAndDescendantIds() : [];

        return CisCategoryManager::forType($this->activeType)
            ->reject(fn (Category $c) => in_array($c->id, $excluded, true))
            ->mapWithKeys(fn (Category $c) => [$c->id => str_repeat('— ', $c->depth) . $c->name])
            ->all();
    }

    /** Optionen für den "Position"-Select: aktuelle Kinder des gewählten Zielelements. */
    public function moveAfterOptions(): array
    {
        if (! $this->formId) {
            return [];
        }

        return Category::where('type', $this->activeType)
            ->where('parent_id', $this->moveTargetParentId)
            ->where('id', '!=', $this->formId)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }

    public function confirmMove(): void
    {
        if (! $this->formId) {
            return;
        }

        $siblingIds = Category::where('type', $this->activeType)
            ->where('parent_id', $this->moveTargetParentId)
            ->where('id', '!=', $this->formId)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $index = $this->moveAfterId
            ? array_search($this->moveAfterId, $siblingIds, true) + 1
            : 0;

        $this->reorder($this->formId, $this->moveTargetParentId, $index);

        // Formular-State auf die neue Position nachziehen (Modal bleibt offen).
        $this->formParentId = $this->moveTargetParentId;
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
