<?php

namespace App\Http\Livewire\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProductTable extends Component
{
    public $searchString;
    public $categoryFilter = '';
    public $orderBy = 'name';
    public $orderDirection = 'ASC';

    protected $queryString = ['searchString'];

    public function render()
    {
        $query = Product::query()->with(['prices', 'childs.category', 'category']);

        if ($this->categoryFilter !== '') {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->searchString) {
            // Bei Suche alle Produkte (inkl. Unterprodukte)
            $query->where('name', 'like', '%' . $this->searchString . '%');
        } else {
            // Nur Oberprodukte: nicht als Kind in product_child eingetragen
            $childIds = DB::table('product_child')->pluck('cis_row_id_child');
            if ($childIds->isNotEmpty()) {
                $query->whereNotIn('cis_row_id', $childIds);
            }
        }

        $products         = $query->orderBy($this->orderBy, $this->orderDirection)->get();
        $categoryOptions  = \CisFoundation\CisCategoryManager\CisCategoryManager::optionsForType('product.category');

        return view('livewire.product.product-table', compact('products', 'categoryOptions'));
    }

    public function resetFilters(): void
    {
        $this->searchString   = null;
        $this->categoryFilter = '';
    }

    public function order($orderName)
    {
        if ($orderName === $this->orderBy) {
            $this->orderDirection = $this->orderDirection === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->orderBy        = $orderName;
            $this->orderDirection = 'ASC';
        }
    }
}
