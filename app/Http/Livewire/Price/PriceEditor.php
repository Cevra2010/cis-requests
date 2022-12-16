<?php

namespace App\Http\Livewire\Price;

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use Livewire\Component;

class PriceEditor extends Component
{

    public $productSource = null;
    public $product = null;
    public $price = null;
    public $productSources;
    public $productSearchName;
    public $productSearchResult = null;
    public $selectedProductResult = null;
    public $started = false;

    public function mount() {
        $this->productSources = ProductSource::all();
    }

    public function render()
    {
        return view('livewire.price.price-editor');
    }

    public function start() {
        $this->started = true;
    }

    public function selectSource(ProductSource $productSource)
    {
        $this->productSource = $productSource;
        $this->emit('focusme','product');
    }

    public function resetSource() {
        $this->productSource = null;
        $this->product = null;
        $this->productSearchName = null;
        $this->productSearchResult = null;
        $this->price = null;
    }

    public function updatedProductSearchName()
    {
        $this->selectedProductResult = null;
        if($this->productSearchName == "") {
            $this->productSearchResult = null;
        }
        else {
            $this->productSearchResult = Product::where('name','like','%'.$this->productSearchName.'%')->take(10)->get();
        }
    }

    public function selectProduct(Product $product) {
        $this->productSearchName = null;
        $this->product = $product;
        $this->emit('focusme','price');
    }

    public function selectArrowProduct() {
        if($this->selectedProductResult) {
            $i=0;
            foreach($this->productSearchResult as $prod) {
                $i++;
                if($i == $this->selectedProductResult) {
                    return $this->selectProduct($prod);
                }
            }
        }
    }

    public function resetProduct() {
        $this->product = null;
        $this->productSearchName = null;
        $this->productSearchResult = null;
        $this->emit('focusme','product');
    }

    public function submitPrice() {
        if($existingPrice = Price::where('cis_row_id_product',$this->product->cis_row_id)->where('cis_row_id_source',$this->productSource->cis_row_id)->first()) {
            $existingPrice->delete();
        }

        if(Price::add($this->price,$this->product,$this->productSource)) {
            $this->product = null;
            $this->price = null;
            $this->productSearchName = null;
            $this->productSearchResult = null;
            $this->emit("focusme",'product');
        }
        else {
            dd("ERROR");
        }
    }

    public function down() {
        if($this->selectedProductResult >= $this->productSearchResult->count())
        {
            $this->selectedProductResult = 1;
        }
        else {
            $this->selectedProductResult++;
        }
    }

    public function up() {
        $this->selectedProductResult--;
        if($this->selectedProductResult == 0){
            $this->selectedProductResult = $this->productSearchResult->count();
        }
    }
}
