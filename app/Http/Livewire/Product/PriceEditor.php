<?php

namespace App\Http\Livewire\Product;

use App\Models\ProductSource;
use Livewire\Component;

class PriceEditor extends Component
{

    public $product = null;
    public $source = null;
    public $price = null;
    public $sourceSearchString = 'Ho';

    public function render()
    {
        $sourcesCollection = ProductSource::where('name','like','%'.$this->sourceSearchString.'%');
        return view("livewire.product.price-editor",[
            'sourcesCollection' => $sourcesCollection->get(),
        ]);
    }

    public function setSource(ProductSource $source) {
        $this->source = $source;
        $this->sourceSearchString = null;
    }
}
