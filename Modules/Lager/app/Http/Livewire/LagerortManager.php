<?php

namespace Modules\Lager\Http\Livewire;

use Livewire\Component;
use Modules\Lager\Models\Lagerort;

/**
 * Lagerort-Baum ("unter Ordnung" erreichbar) – bewusst eine eigene Komponente
 * statt einer Erweiterung von App\Http\Livewire\Category\CategoryManager, da
 * Lagerort ein eigenes Modell mit String-UUID-PK und fachfremden Beziehungen
 * (Projekte, Bestand) ist. UX/Baum-Mechanik (SortableJS Drag&Drop, "Verschieben
 * nach..."-Alternative, Zyklusschutz) ist bewusst identisch zu CategoryManager
 * gehalten, siehe resources/views/livewire/category/category-manager.blade.php.
 */
class LagerortManager extends Component
{
    public bool $showFormModal = false;
    public ?string $formId = null;
    public ?string $formParentId = null;
    public string $formName = '';

    public ?string $moveTargetParentId = null;
    public ?string $moveAfterId = null;

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
        $lagerort = Lagerort::findOrFail($id);

        $this->formId         = $lagerort->cis_row_id;
        $this->formParentId   = $lagerort->cis_row_id_parent;
        $this->formName       = $lagerort->name;
        $this->showFormModal  = true;

        $this->moveTargetParentId = $lagerort->cis_row_id_parent;
        $siblingIds = Lagerort::where('cis_row_id_parent', $lagerort->cis_row_id_parent)
            ->orderBy('sort_order')->pluck('cis_row_id')->all();
        $ownIndex = array_search($id, $siblingIds, true);
        $this->moveAfterId = ($ownIndex !== false && $ownIndex > 0) ? $siblingIds[$ownIndex - 1] : null;
    }

    public function save(): void
    {
        $this->validate(['formName' => 'required|string|max:255']);

        if ($this->formId) {
            // Reihenfolge/Elternelement werden ausschließlich über reorder() geändert.
            Lagerort::where('cis_row_id', $this->formId)->update(['name' => $this->formName]);
        } else {
            $maxOrder = Lagerort::where('cis_row_id_parent', $this->formParentId)->max('sort_order') ?? -1;
            Lagerort::create([
                'cis_row_id_parent' => $this->formParentId,
                'name'              => $this->formName,
                'sort_order'        => $maxOrder + 1,
            ]);
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
        $this->moveTargetParentId = null;
        $this->moveAfterId        = null;
        $this->resetErrorBag();
    }

    // ── Verschieben (Drag & Drop + Formular-Alternative) ────────────────────

    public function reorder(string $id, ?string $newParentId, int $newIndex): void
    {
        $node = Lagerort::findOrFail($id);

        // Schutz: nicht in sich selbst oder einen eigenen Nachfahren verschieben.
        if ($newParentId && in_array($newParentId, $node->selfAndDescendantIds(), true)) {
            return;
        }

        $node->update(['cis_row_id_parent' => $newParentId]);

        $siblings = Lagerort::where('cis_row_id_parent', $newParentId)
            ->where('cis_row_id', '!=', $id)
            ->orderBy('sort_order')
            ->pluck('cis_row_id')
            ->all();

        array_splice($siblings, max(0, min($newIndex, count($siblings))), 0, [$id]);

        foreach ($siblings as $position => $siblingId) {
            Lagerort::where('cis_row_id', $siblingId)->update(['sort_order' => $position]);
        }
    }

    public function updatedMoveTargetParentId(): void
    {
        $this->moveAfterId = null;
    }

    public function moveTargetOptions(): array
    {
        if (! $this->formId) {
            return [];
        }
        $node     = Lagerort::find($this->formId);
        $excluded = $node ? $node->selfAndDescendantIds() : [];

        return Lagerort::flatTree()
            ->reject(fn (Lagerort $l) => in_array($l->cis_row_id, $excluded, true))
            ->mapWithKeys(fn (Lagerort $l) => [$l->cis_row_id => str_repeat('— ', $l->depth) . $l->name])
            ->all();
    }

    public function moveAfterOptions(): array
    {
        if (! $this->formId) {
            return [];
        }

        return Lagerort::where('cis_row_id_parent', $this->moveTargetParentId)
            ->where('cis_row_id', '!=', $this->formId)
            ->orderBy('sort_order')
            ->pluck('name', 'cis_row_id')
            ->all();
    }

    public function confirmMove(): void
    {
        if (! $this->formId) {
            return;
        }

        $siblingIds = Lagerort::where('cis_row_id_parent', $this->moveTargetParentId)
            ->where('cis_row_id', '!=', $this->formId)
            ->orderBy('sort_order')
            ->pluck('cis_row_id')
            ->all();

        $index = $this->moveAfterId
            ? array_search($this->moveAfterId, $siblingIds, true) + 1
            : 0;

        $this->reorder($this->formId, $this->moveTargetParentId, $index);

        $this->formParentId = $this->moveTargetParentId;
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
        $lagerort = Lagerort::findOrFail($this->deleteId);

        if ($this->deleteConfirmText !== 'DEL-' . $lagerort->name) {
            $this->addError('deleteConfirmText', 'Sicherheitsabfrage ist nicht korrekt.');
            return;
        }

        Lagerort::whereIn('cis_row_id', $lagerort->selfAndDescendantIds())->delete();

        $this->showDeleteModal  = false;
        $this->deleteId         = null;
        $this->deleteConfirmText = '';
    }

    public function render()
    {
        $tree            = Lagerort::tree();
        $deleteLagerort  = $this->deleteId ? Lagerort::find($this->deleteId) : null;
        $deleteDescCount = $deleteLagerort ? count($deleteLagerort->selfAndDescendantIds()) - 1 : 0;

        return view('lager::livewire.lagerort-manager', compact('tree', 'deleteLagerort', 'deleteDescCount'));
    }
}
