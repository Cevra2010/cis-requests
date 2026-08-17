<?php

namespace App\Http\Livewire\Product;

use App\Models\Product;
use Livewire\Component;

class AddChild extends Component
{

    public $parent;
    public $searchString;
    public $selectedProduct = 0;
    public $products = null;
    public $selectedProductObject = null;

    public function mount(Product $parent) {
        $this->parent = $parent;
    }

    public function render()
    {
        return view('livewire.product.add-child');
    }

    public function updatedSearchString() {
        if($this->searchString) {
            $this->products = Product::where('name','like','%'.$this->searchString.'%')->take(10)->get();
        }
        else
        {
            $this->products = null;
        }
        $this->selectedProduct = 0;
    }

    public function down() {
        if(!$this->products) return null;
        if($this->selectedProduct == $this->products->count()) {
            $this->selectedProduct = 1;
        }
        else {
            $this->selectedProduct++;
        }
    }

    public function up() {
        if(!$this->products) return null;
        if($this->selectedProduct == 1) {
            $this->selectedProduct = $this->products->count();
        }
        else {
            $this->selectedProduct--;
        }
    }

    public function selectProduct($product = null){
        if(!$product)
        {
            $i = 1;
            foreach($this->products as $productLoopItem) {
                if($i == $this->selectedProduct) {
                    $product = $productLoopItem;
                }
                $i++;
            }
        }
        else {
            $product = $this->products->where('cis_row_id',$product)->first();
        }

        $this->selectedProductObject = $product;
        $this->products = null;
    }

    public function submitForm() {
        if($this->selectedProductObject) {
            $product = $this->selectedProductObject;
        }
        else {
            $product = new Product();
            $product->name = $this->searchString;
            $product->save();
        }

        $this->parent->childs()->attach($product);
        return redirect()->route("product.edit",$this->parent);
    }
}
