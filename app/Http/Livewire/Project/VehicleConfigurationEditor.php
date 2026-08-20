<?php

namespace App\Http\Livewire\Project;

use App\Http\Livewire\Concerns\RespectsProjectLock;
use App\Models\Project;
use App\Models\ProjectVehicleBlock;
use App\Models\ProjectVehicleBlockItem;
use App\Models\TemplateParameter;
use Livewire\Component;

class VehicleConfigurationEditor extends Component
{
    use RespectsProjectLock;

    public string $projectId;

    public bool $showParameterBrowser = false;
    public ?string $parameterBrowserBlockId = null;
    public string $parameterSearch = '';

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function render()
    {
        $blocks = ProjectVehicleBlock::where('cis_row_id_project', $this->projectId)
            ->with('items')
            ->orderBy('sort_order')
            ->get();

        $project = Project::where('cis_row_id', $this->projectId)->first();
        $canEdit = $project?->isEditableBy(auth()->user()) ?? true;

        $parameterTree    = null;
        $parameterResults = null;

        if ($this->showParameterBrowser) {
            if (trim($this->parameterSearch) !== '') {
                $parameterResults = TemplateParameter::where('name', 'like', '%' . trim($this->parameterSearch) . '%')
                    ->orWhere('description', 'like', '%' . trim($this->parameterSearch) . '%')
                    ->orderBy('name')
                    ->get();
            } else {
                $parameterTree = TemplateParameter::whereNull('cis_row_id_parent')
                    ->with('children')
                    ->orderBy('sort_order')->orderBy('name')
                    ->get();
            }
        }

        return view('livewire.project.vehicle-configuration-editor', compact(
            'blocks', 'canEdit', 'parameterTree', 'parameterResults'
        ));
    }

    // ── Blöcke ───────────────────────────────────────────────────────────────

    public function addBlock(): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $maxOrder = ProjectVehicleBlock::where('cis_row_id_project', $this->projectId)->max('sort_order') ?? 0;

        ProjectVehicleBlock::create([
            'cis_row_id_project' => $this->projectId,
            'title'              => 'Neuer Block',
            'sort_order'         => (int) $maxOrder + 1,
        ]);
    }

    public function renameBlock(string $blockId, string $title): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        ProjectVehicleBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->update(['title' => $title ?: 'Neuer Block']);
    }

    public function removeBlock(string $blockId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        ProjectVehicleBlockItem::where('cis_row_id_block', $blockId)->delete();
        ProjectVehicleBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->delete();
    }

    public function moveBlockUp(string $blockId): void
    {
        $this->swapBlocks($blockId, 'up');
    }

    public function moveBlockDown(string $blockId): void
    {
        $this->swapBlocks($blockId, 'down');
    }

    private function swapBlocks(string $blockId, string $direction): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $blocks       = ProjectVehicleBlock::where('cis_row_id_project', $this->projectId)->orderBy('sort_order')->get();
        $currentIndex = $blocks->search(fn ($b) => $b->cis_row_id === $blockId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex < 0 || $targetIndex >= $blocks->count()) {
            return;
        }

        $current = $blocks[$currentIndex];
        $target  = $blocks[$targetIndex];

        ProjectVehicleBlock::where('cis_row_id', $current->cis_row_id)->update(['sort_order' => $target->sort_order]);
        ProjectVehicleBlock::where('cis_row_id', $target->cis_row_id)->update(['sort_order' => $current->sort_order]);
    }

    // ── Freitext-Einträge ────────────────────────────────────────────────────

    public function addTextItem(string $blockId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $maxOrder = ProjectVehicleBlockItem::where('cis_row_id_block', $blockId)->max('sort_order') ?? 0;

        ProjectVehicleBlockItem::create([
            'cis_row_id_block' => $blockId,
            'type'             => 'text',
            'text'             => '',
            'sort_order'       => (int) $maxOrder + 1,
        ]);
    }

    public function updateItemText(string $itemId, string $text): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        ProjectVehicleBlockItem::where('cis_row_id', $itemId)->update(['text' => $text]);
    }

    public function removeItem(string $itemId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        ProjectVehicleBlockItem::where('cis_row_id', $itemId)->delete();
    }

    public function moveItemUp(string $blockId, string $itemId): void
    {
        $this->swapItems($blockId, $itemId, 'up');
    }

    public function moveItemDown(string $blockId, string $itemId): void
    {
        $this->swapItems($blockId, $itemId, 'down');
    }

    private function swapItems(string $blockId, string $itemId, string $direction): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $items        = ProjectVehicleBlockItem::where('cis_row_id_block', $blockId)->orderBy('sort_order')->get();
        $currentIndex = $items->search(fn ($i) => $i->cis_row_id === $itemId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex < 0 || $targetIndex >= $items->count()) {
            return;
        }

        $current = $items[$currentIndex];
        $target  = $items[$targetIndex];

        ProjectVehicleBlockItem::where('cis_row_id', $current->cis_row_id)->update(['sort_order' => $target->sort_order]);
        ProjectVehicleBlockItem::where('cis_row_id', $target->cis_row_id)->update(['sort_order' => $current->sort_order]);
    }

    // ── Parameter-Browser ────────────────────────────────────────────────────

    public function openParameterBrowser(string $blockId): void
    {
        $this->parameterBrowserBlockId = $blockId;
        $this->parameterSearch         = '';
        $this->showParameterBrowser    = true;
    }

    public function closeParameterBrowser(): void
    {
        $this->showParameterBrowser    = false;
        $this->parameterBrowserBlockId = null;
    }

    /**
     * Übernimmt einen Parameter (und rekursiv alle Unter-Parameter) als
     * einzelne, ab jetzt frei editierbare Text-Einträge in den Block.
     */
    public function insertParameter(string $parameterId): void
    {
        if (! $this->assertEditable($this->projectId) || ! $this->parameterBrowserBlockId) {
            return;
        }

        $parameter = TemplateParameter::with('children')->findOrFail($parameterId);
        $blockId   = $this->parameterBrowserBlockId;

        $maxOrder = (int) (ProjectVehicleBlockItem::where('cis_row_id_block', $blockId)->max('sort_order') ?? 0);

        foreach ($parameter->selfAndDescendantsFlat() as $entry) {
            /** @var TemplateParameter $p */
            $p = $entry['parameter'];
            $maxOrder++;

            $prefix = str_repeat('　↳ ', $entry['depth']);
            $text   = $p->description ? "{$p->name}: {$p->description}" : $p->name;

            ProjectVehicleBlockItem::create([
                'cis_row_id_block'      => $blockId,
                'type'                  => 'parameter',
                'text'                  => $prefix . $text,
                'cis_row_id_parameter'  => $p->cis_row_id,
                'source_label'          => $p->name,
                'sort_order'            => $maxOrder,
            ]);
        }

        $this->closeParameterBrowser();
    }
}
