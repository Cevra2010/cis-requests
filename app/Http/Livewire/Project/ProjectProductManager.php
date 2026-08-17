<?php

namespace App\Http\Livewire\Project;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectProductManager extends Component
{
    public string $projectId;
    public string $search = '';

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    #[On('positions-imported')]
    public function refresh(): void
    {
        // Löst lediglich ein Re-Render aus, damit importierte Positionen sichtbar werden.
    }

    public function render()
    {
        $assigned = DB::table('project_product')
            ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
            ->where('project_product.cis_row_id_project', $this->projectId)
            ->whereNull('products.deleted_at')
            ->orderBy('project_product.sort_order')
            ->select(
                'products.cis_row_id',
                'products.name',
                'project_product.product_count',
                'project_product.note',
                'project_product.sort_order'
            )
            ->get();

        $assignedIds = $assigned->pluck('cis_row_id')->toArray();

        $available = Product::whereNull('deleted_at')
            ->whereNotIn('cis_row_id', $assignedIds)
            ->when(trim($this->search), fn($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
            ->orderBy('name')
            ->get(['cis_row_id', 'name']);

        return view('livewire.project.project-product-manager', compact('assigned', 'available'));
    }

    public function add(string $productId): void
    {
        $alreadyAdded = DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_product', $productId)
            ->exists();

        if ($alreadyAdded) {
            return;
        }

        $maxOrder = DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->max('sort_order') ?? 0;

        DB::table('project_product')->insert([
            'cis_row_id_project' => $this->projectId,
            'cis_row_id_product' => $productId,
            'product_count'      => 1,
            'note'               => null,
            'sort_order'         => (int) $maxOrder + 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function remove(string $productId): void
    {
        DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_product', $productId)
            ->delete();
    }

    public function moveUp(string $productId): void
    {
        $this->swapOrder($productId, 'up');
    }

    public function moveDown(string $productId): void
    {
        $this->swapOrder($productId, 'down');
    }

    public function updateCount(string $productId, $value): void
    {
        DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_product', $productId)
            ->update(['product_count' => max(1, (int) $value), 'updated_at' => now()]);
    }

    public function updateNote(string $productId, string $value): void
    {
        DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_product', $productId)
            ->update(['note' => $value ?: null, 'updated_at' => now()]);
    }

    private function swapOrder(string $productId, string $direction): void
    {
        $items = DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->orderBy('sort_order')
            ->get();

        $currentIndex = $items->search(fn($i) => $i->cis_row_id_product === $productId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex < 0 || $targetIndex >= $items->count()) {
            return;
        }

        $current = $items[$currentIndex];
        $target  = $items[$targetIndex];

        DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_product', $current->cis_row_id_product)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('project_product')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_product', $target->cis_row_id_product)
            ->update(['sort_order' => $current->sort_order]);
    }
}
