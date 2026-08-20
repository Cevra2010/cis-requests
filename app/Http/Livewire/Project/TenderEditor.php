<?php

namespace App\Http\Livewire\Project;

use App\Http\Livewire\Concerns\RespectsProjectLock;
use App\Models\ProductDescription;
use App\Models\Project;
use App\Models\ProjectProduct;
use App\Models\ProjectTenderBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TenderEditor extends Component
{
    use RespectsProjectLock;

    public string $projectId;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function render()
    {
        $blocks     = ProjectTenderBlock::where('cis_row_id_project', $this->projectId)
            ->orderBy('sort_order')
            ->get();

        $validation = $this->computeValidation($blocks);

        $project = Project::where('cis_row_id', $this->projectId)->first();
        $canEdit = $project?->isEditableBy(auth()->user()) ?? true;

        $estimate = $this->estimateCost();

        return view('livewire.project.tender-editor', compact('blocks', 'validation', 'canEdit', 'estimate'));
    }

    // ── Block management ──────────────────────────────────────────────────────

    public function addBlock(string $type): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $maxOrder = ProjectTenderBlock::where('cis_row_id_project', $this->projectId)
            ->max('sort_order') ?? 0;

        ProjectTenderBlock::create([
            'cis_row_id_project' => $this->projectId,
            'type'               => $type,
            'sort_order'         => (int) $maxOrder + 1,
            'config'             => match($type) {
                    'heading' => ['text' => 'Neue Überschrift'],
                    'text'    => ['text' => ''],
                    'space'   => ['height' => 40],
                    default   => null,
                },
        ]);
    }

    public function addBlockAt(string $type, int $position): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $blocks = ProjectTenderBlock::where('cis_row_id_project', $this->projectId)
            ->orderBy('sort_order')
            ->get();

        foreach ($blocks->slice($position) as $block) {
            ProjectTenderBlock::where('cis_row_id', $block->cis_row_id)
                ->update(['sort_order' => $block->sort_order + 1]);
        }

        ProjectTenderBlock::create([
            'cis_row_id_project' => $this->projectId,
            'type'               => $type,
            'sort_order'         => $position + 1,
            'config'             => match($type) {
                    'heading' => ['text' => 'Neue Überschrift'],
                    'text'    => ['text' => ''],
                    'space'   => ['height' => 40],
                    default   => null,
                },
        ]);
    }

    public function toggleBlockLabel(string $blockId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $block = ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->firstOrFail();

        $config = $block->config ?? [];
        $block->update(['config' => array_merge($config, [
            'show_label' => !($config['show_label'] ?? false),
        ])]);
    }

    public function removeBlock(string $blockId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->delete();
    }

    public function copyBlock(string $blockId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $original = ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->firstOrFail();

        $maxOrder = ProjectTenderBlock::where('cis_row_id_project', $this->projectId)
            ->max('sort_order') ?? 0;

        ProjectTenderBlock::create([
            'cis_row_id_project' => $this->projectId,
            'type'               => $original->type,
            'sort_order'         => (int) $maxOrder + 1,
            'config'             => $original->config,
        ]);
    }

    public function reorder(array $orderedIds): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        foreach ($orderedIds as $index => $id) {
            ProjectTenderBlock::where('cis_row_id', $id)
                ->where('cis_row_id_project', $this->projectId)
                ->update(['sort_order' => $index + 1]);
        }
    }

    public function moveUp(string $blockId): void
    {
        $this->swapBlocks($blockId, 'up');
    }

    public function moveDown(string $blockId): void
    {
        $this->swapBlocks($blockId, 'down');
    }

    // ── Content editing ───────────────────────────────────────────────────────

    public function updateHeadingText(string $blockId, string $text): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $block = ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->firstOrFail();

        $block->update(['config' => array_merge($block->config ?? [], ['text' => $text])]);
    }

    public function updateTextBlock(string $blockId, string $text): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $block = ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->firstOrFail();

        $block->update(['config' => array_merge($block->config ?? [], ['text' => $text])]);
    }

    public function updateSpaceHeight(string $blockId, int $height): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $block = ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->firstOrFail();

        $block->update(['config' => ['height' => max(10, min(400, $height))]]);
    }

    public function updateProductDescription(string $productId, string $text): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $existing = DB::table('product_descriptions')
            ->where('cis_row_id_product', $productId)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            DB::table('product_descriptions')
                ->where('cis_row_id', $existing->cis_row_id)
                ->update(['text' => $text, 'updated_at' => now()]);
        } else {
            $desc                     = new ProductDescription();
            $desc->cis_row_id_product = $productId;
            $desc->text               = $text;
            $desc->save();
        }
    }

    // ── Per-block item selection ───────────────────────────────────────────────

    /**
     * Toggle a parent item (product) in/out of a block's selection.
     * null config['selected'] = "all items" (implicit).
     */
    public function toggleBlockItem(string $blockId, string $itemId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $block = ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->firstOrFail();

        $config   = $block->config ?? [];
        $selected = $config['selected'] ?? null;

        if ($selected === null) {
            $allIds   = $this->getAllItemIds();
            $selected = array_values(array_filter($allIds, fn($id) => $id !== $itemId));
        } else {
            if (in_array($itemId, $selected, true)) {
                $selected = array_values(array_filter($selected, fn($id) => $id !== $itemId));
            } else {
                $selected[] = $itemId;
            }
        }

        $block->update(['config' => array_merge($config, ['selected' => $selected])]);
    }

    /**
     * Toggle a child/sub-product in/out of a block's excluded_children list.
     * excluded_children = [] means all children visible (default).
     */
    public function toggleChildItem(string $blockId, string $childId): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $block = ProjectTenderBlock::where('cis_row_id', $blockId)
            ->where('cis_row_id_project', $this->projectId)
            ->firstOrFail();

        $config   = $block->config ?? [];
        $excluded = $config['excluded_children'] ?? [];

        if (in_array($childId, $excluded, true)) {
            $excluded = array_values(array_filter($excluded, fn($id) => $id !== $childId));
        } else {
            $excluded[] = $childId;
        }

        $block->update(['config' => array_merge($config, ['excluded_children' => $excluded])]);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function computeValidation(Collection $blocks): array
    {
        $allProdIds = DB::table('project_product')
            ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
            ->where('project_product.cis_row_id_project', $this->projectId)
            ->whereNull('products.deleted_at')
            ->pluck('project_product.cis_row_id_product')
            ->toArray();

        $coveredProdIds = [];

        foreach ($blocks as $block) {
            if ($block->type === 'products') {
                $selected       = $block->config['selected'] ?? null;
                $coveredProdIds = array_merge($coveredProdIds, $selected ?? $allProdIds);
            }
        }

        $coveredProdIds = array_unique($coveredProdIds);
        $missingProdIds = array_diff($allProdIds, $coveredProdIds);

        $missingProdNames = DB::table('products')
            ->whereIn('cis_row_id', $missingProdIds)
            ->pluck('name')
            ->toArray();

        return [
            'total_prods'   => count($allProdIds),
            'covered_prods' => count($coveredProdIds),
            'missing_prods' => $missingProdNames,
            'all_ok'        => empty($missingProdIds),
        ];
    }

    /**
     * Grobe Kostenschätzung auf Basis der zuletzt erfassten Katalogpreise
     * (Produkt + Unterprodukte). Ersetzt keine echten Angebote – dient nur
     * zur groben Orientierung vor der Ausschreibung.
     */
    private function estimateCost(): array
    {
        $positions = ProjectProduct::where('cis_row_id_project', $this->projectId)
            ->with('product')
            ->get();

        $total          = 0.0;
        $missingCount   = 0;
        $positionsCount = $positions->count();

        foreach ($positions as $position) {
            if (! $position->product) {
                continue;
            }
            $groupPrice = $position->product->getGroupPrice();
            if ($groupPrice <= 0) {
                $missingCount++;
                continue;
            }
            $total += $groupPrice * $position->product_count;
        }

        return [
            'total'           => $total,
            'positions_count' => $positionsCount,
            'missing_count'   => $missingCount,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getAllItemIds(): array
    {
        return DB::table('project_product')
            ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
            ->where('project_product.cis_row_id_project', $this->projectId)
            ->whereNull('products.deleted_at')
            ->pluck('project_product.cis_row_id_product')
            ->toArray();
    }

    public function childrenForProduct(string $productId): \Illuminate\Support\Collection
    {
        return DB::table('product_child')
            ->join('products', 'product_child.cis_row_id_child', '=', 'products.cis_row_id')
            ->where('product_child.cis_row_id_parent', $productId)
            ->whereNull('products.deleted_at')
            ->select('products.cis_row_id', 'products.name')
            ->get();
    }

    private function swapBlocks(string $blockId, string $direction): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        $blocks = ProjectTenderBlock::where('cis_row_id_project', $this->projectId)
            ->orderBy('sort_order')
            ->get();

        $currentIndex = $blocks->search(fn($b) => $b->cis_row_id === $blockId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex < 0 || $targetIndex >= $blocks->count()) {
            return;
        }

        $current = $blocks[$currentIndex];
        $target  = $blocks[$targetIndex];

        ProjectTenderBlock::where('cis_row_id', $current->cis_row_id)->update(['sort_order' => $target->sort_order]);
        ProjectTenderBlock::where('cis_row_id', $target->cis_row_id)->update(['sort_order' => $current->sort_order]);
    }
}
