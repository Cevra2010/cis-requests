<?php

namespace Modules\Ausschreibungsvorlagen\Http\Livewire;

use Livewire\Component;
use Modules\Ausschreibungsvorlagen\Models\TenderTemplate;
use Modules\Ausschreibungsvorlagen\Models\TenderTemplateBlock;

class TemplateManager extends Component
{
    public string $newTemplateName = '';

    public ?string $expandedTemplateId = null;

    public function render()
    {
        return view('ausschreibungsvorlagen::livewire.template-manager', [
            'templates' => TenderTemplate::with('blocks')->orderBy('name')->get(),
        ]);
    }

    public function createTemplate(): void
    {
        $this->validate(['newTemplateName' => 'required|string|max:255']);

        $template = TenderTemplate::create(['name' => $this->newTemplateName]);

        $this->newTemplateName    = '';
        $this->expandedTemplateId = $template->cis_row_id;
    }

    public function renameTemplate(string $id, string $name): void
    {
        if (trim($name) === '') {
            return;
        }

        TenderTemplate::where('cis_row_id', $id)->update(['name' => trim($name)]);
    }

    public function deleteTemplate(string $id): void
    {
        TenderTemplateBlock::where('cis_row_id_template', $id)->delete();
        TenderTemplate::where('cis_row_id', $id)->delete();

        if ($this->expandedTemplateId === $id) {
            $this->expandedTemplateId = null;
        }
    }

    public function toggleExpanded(string $id): void
    {
        $this->expandedTemplateId = $this->expandedTemplateId === $id ? null : $id;
    }

    // ── Block management ──────────────────────────────────────────────────────

    public function addBlock(string $templateId, string $type): void
    {
        $maxOrder = TenderTemplateBlock::where('cis_row_id_template', $templateId)->max('sort_order') ?? 0;

        TenderTemplateBlock::create([
            'cis_row_id_template' => $templateId,
            'type'                => $type,
            'sort_order'          => (int) $maxOrder + 1,
            'config'              => match ($type) {
                'heading' => ['text' => 'Neue Überschrift'],
                'text'    => ['text' => ''],
                'space'   => ['height' => 40],
                default   => null,
            },
        ]);
    }

    public function removeBlock(string $blockId): void
    {
        TenderTemplateBlock::where('cis_row_id', $blockId)->delete();
    }

    public function moveBlock(string $blockId, string $direction): void
    {
        $block = TenderTemplateBlock::find($blockId);
        if (! $block) {
            return;
        }

        $blocks       = TenderTemplateBlock::where('cis_row_id_template', $block->cis_row_id_template)
            ->orderBy('sort_order')
            ->get();
        $currentIndex = $blocks->search(fn ($b) => $b->cis_row_id === $blockId);
        $targetIndex  = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($currentIndex === false || $targetIndex < 0 || $targetIndex >= $blocks->count()) {
            return;
        }

        $target = $blocks[$targetIndex];
        TenderTemplateBlock::where('cis_row_id', $block->cis_row_id)->update(['sort_order' => $target->sort_order]);
        TenderTemplateBlock::where('cis_row_id', $target->cis_row_id)->update(['sort_order' => $block->sort_order]);
    }

    public function updateBlockText(string $blockId, string $text): void
    {
        $block = TenderTemplateBlock::find($blockId);
        if (! $block) {
            return;
        }

        $block->update(['config' => array_merge($block->config ?? [], ['text' => $text])]);
    }

    public function updateSpaceHeight(string $blockId, int $height): void
    {
        TenderTemplateBlock::where('cis_row_id', $blockId)
            ->update(['config' => ['height' => max(10, min(400, $height))]]);
    }
}
