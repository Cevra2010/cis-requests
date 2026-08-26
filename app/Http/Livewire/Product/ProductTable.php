<?php

namespace App\Http\Livewire\Product;

use App\Models\Product;
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
            $query->where('name', 'like', '%' . $this->searchString . '%');
        }

        // Ein Produkt, das als Unterprodukt verknüpft ist, bleibt trotzdem ein
        // eigenständiges Hauptprodukt und erscheint daher immer auch hier in
        // der Liste (zusätzlich zur Vorschau unter seinen Elternprodukten).

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
