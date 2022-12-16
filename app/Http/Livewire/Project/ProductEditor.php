<?php

namespace App\Http\Livewire\Project;

use App\Models\Product;
use App\Models\Project;
use Livewire\Component;

class ProductEditor extends Component
{

    public $project;
    public $products;
    public $selectedProduct = null;
    protected $rules = [
        'selectedProduct.pivot.product_count' => 'required',
    ];

    public function mount(Project $project) {
        $this->project = $project;
        $this->reloadProductList();
    }

    public function render()
    {
        $linkedProducts = $this->project->products()->withPivot(['product_count'])->get();
        return view('livewire.project.product-editor',[
            'linkedProducts' => $linkedProducts,
        ]);
    }

    public function add(Product $product) {
        foreach($this->products as $key => $prod) {
            if($prod->cis_row_id == $product->cis_row_id) {
                $this->products->forget($key);
            }
        }
        $this->project->products()->attach($product,['product_count' => 1]);
        $this->selectProduct($product->cis_row_id);
    }

    public function selectProduct($cis_row_id) {
        $this->selectedProduct = $this->project->products()->withPivot(['product_count'])->where('cis_row_id',$cis_row_id)->first();
    }

    public function submitProduct() {
       $this->project->products()->updateExistingPivot(['cis_row_id_product',$this->selectedProduct->cis_row_id],$this->selectedProduct->pivot);

    }

    public function delete() {
        $this->project->products()->detach($this->selectedProduct);
        $this->selectedProduct = null;
        $this->reloadProductList();

    }

    private function reloadProductList() {
        $this->products = Product::orderBy('name')->get();

        foreach($this->products as $key => $prod) {
            if($this->project->products()->where("cis_row_id",$prod->cis_row_id)->count()) {
                $this->products->forget($key);
            }
        }
    }
}
