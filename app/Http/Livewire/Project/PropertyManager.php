<?php

namespace App\Http\Livewire\Project;

use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PropertyManager extends Component
{
    public string $projectId;
    public string $search = '';
    public array $searchResults = [];

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function render()
    {
        $assigned = DB::table('project_property')
            ->join('properties', 'project_property.cis_row_id_property', '=', 'properties.cis_row_id')
            ->where('project_property.cis_row_id_project', $this->projectId)
            ->whereNull('properties.deleted_at')
            ->orderBy('project_property.sort_order')
            ->select(
                'properties.cis_row_id',
                'properties.name',
                'properties.description as default_description',
                'project_property.custom_description',
                'project_property.sort_order'
            )
            ->get();

        return view('livewire.project.property-manager', compact('assigned'));
    }

    public function updatedSearch(): void
    {
        if (strlen(trim($this->search)) < 2) {
            $this->searchResults = [];
            return;
        }

        $assignedIds = DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->pluck('cis_row_id_property')
            ->toArray();

        $results = Property::where('name', 'like', '%' . trim($this->search) . '%')
            ->whereNotIn('cis_row_id', $assignedIds)
            ->orderBy('name')
            ->limit(8)
            ->get();

        $this->searchResults = $results->map(fn($p) => [
            'id'   => (string) $p->cis_row_id,
            'name' => $p->name,
        ])->toArray();
    }

    public function add(string $propertyId): void
    {
        if (DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_property', $propertyId)
            ->exists()) {
            return;
        }

        $maxOrder = DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->max('sort_order') ?? 0;

        DB::table('project_property')->insert([
            'cis_row_id_project'  => $this->projectId,
            'cis_row_id_property' => $propertyId,
            'sort_order'          => (int) $maxOrder + 1,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->search        = '';
        $this->searchResults = [];
    }

    public function remove(string $propertyId): void
    {
        DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_property', $propertyId)
            ->delete();
    }

    public function moveUp(string $propertyId): void
    {
        $this->swapOrder($propertyId, 'up');
    }

    public function moveDown(string $propertyId): void
    {
        $this->swapOrder($propertyId, 'down');
    }

    public function updateDescription(string $propertyId, string $value): void
    {
        DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_property', $propertyId)
            ->update(['custom_description' => $value ?: null, 'updated_at' => now()]);
    }

    public function resetDescription(string $propertyId): void
    {
        DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_property', $propertyId)
            ->update(['custom_description' => null, 'updated_at' => now()]);
    }

    private function swapOrder(string $propertyId, string $direction): void
    {
        $items = DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->orderBy('sort_order')
            ->get();

        $currentIndex = $items->search(fn($i) => $i->cis_row_id_property === $propertyId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex < 0 || $targetIndex >= $items->count()) {
            return;
        }

        $current = $items[$currentIndex];
        $target  = $items[$targetIndex];

        DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_property', $current->cis_row_id_property)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('project_property')
            ->where('cis_row_id_project', $this->projectId)
            ->where('cis_row_id_property', $target->cis_row_id_property)
            ->update(['sort_order' => $current->sort_order]);
    }
}
