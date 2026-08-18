<?php

namespace App\Http\Livewire\Project;

use App\Http\Livewire\Concerns\RespectsProjectLock;
use App\Models\Project;
use App\Models\ProjectProduct;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ImportPositionsFromProject extends Component
{
    use RespectsProjectLock;

    public string $projectId;
    public bool   $open = false;

    public string $search = '';
    public ?string $sourceProjectId = null;

    /** cis_row_id_product => Menge, für aktuell zur Übernahme selektierte Positionen */
    public array $selected = [];

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function openModal(): void
    {
        $this->open            = true;
        $this->search          = '';
        $this->sourceProjectId = null;
        $this->selected        = [];
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function selectSourceProject(string $projectId): void
    {
        $this->sourceProjectId = $projectId;

        $this->selected = [];
        foreach ($this->sourcePositions() as $pos) {
            $this->selected[$pos->cis_row_id_product] = $pos->product_count;
        }
    }

    public function toggle(string $productId): void
    {
        if (array_key_exists($productId, $this->selected)) {
            unset($this->selected[$productId]);
            return;
        }

        $pos = $this->sourcePositions()->firstWhere('cis_row_id_product', $productId);
        $this->selected[$productId] = $pos->product_count ?? 1;
    }

    public function updateQty(string $productId, $value): void
    {
        if (array_key_exists($productId, $this->selected)) {
            $this->selected[$productId] = max(1, (int) $value);
        }
    }

    public function import(): void
    {
        if (! $this->assertEditable($this->projectId)) {
            return;
        }

        if (! $this->sourceProjectId || empty($this->selected)) {
            return;
        }

        $existingIds = ProjectProduct::where('cis_row_id_project', $this->projectId)
            ->pluck('cis_row_id_product')
            ->toArray();

        $maxOrder = ProjectProduct::where('cis_row_id_project', $this->projectId)->max('sort_order') ?? 0;

        $sourcePositions = $this->sourcePositions()->keyBy('cis_row_id_product');

        foreach ($this->selected as $productId => $qty) {
            if (in_array($productId, $existingIds, true)) {
                continue;
            }

            $maxOrder++;
            ProjectProduct::create([
                'cis_row_id_project' => $this->projectId,
                'cis_row_id_product' => $productId,
                'product_count'      => $qty,
                'note'               => $sourcePositions[$productId]->note ?? null,
                'sort_order'         => $maxOrder,
            ]);
        }

        $this->open     = false;
        $this->selected = [];
        session()->flash('success', 'Positionen wurden als Vorlage übernommen.');
        $this->dispatch('positions-imported');
    }

    private function sourcePositions()
    {
        if (! $this->sourceProjectId) {
            return collect();
        }

        return DB::table('project_product')
            ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
            ->where('project_product.cis_row_id_project', $this->sourceProjectId)
            ->whereNull('products.deleted_at')
            ->orderBy('project_product.sort_order')
            ->select(
                'products.cis_row_id as product_id',
                'products.name',
                'project_product.cis_row_id_product',
                'project_product.product_count',
                'project_product.note'
            )
            ->get();
    }

    public function render()
    {
        $projects = Project::where('cis_row_id', '!=', $this->projectId)
            ->when(trim($this->search), fn ($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get(['cis_row_id', 'name', 'tender_year']);

        $sourcePositions = $this->sourcePositions();

        $project = Project::where('cis_row_id', $this->projectId)->first();
        $canEdit = $project?->isEditableBy(auth()->user()) ?? true;

        return view('livewire.project.import-positions-from-project', compact('projects', 'sourcePositions', 'canEdit'));
    }
}
