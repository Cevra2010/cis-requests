<?php

namespace App\Http\Livewire\Product;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductParameter;

class ProductParameterEditor extends Component
{

    public $product;
    public $newParameterText = null;

    public function mount(Product $product) {
        $this->product = $product;
    }

    public function render()
    {
        $parameters = $this->product->parameters()->get();
        return view('livewire.product.product-parameter-editor',[
            'parameters' => $parameters,
        ]);
    }

    public function store() {
        if($this->newParameterText == null) {
            $this->errorBag->add('parameter','Bitte geben Sie ein Parameter an.');
        }
        $parameter = new ProductParameter();
        $parameter->text = $this->newParameterText;
        $parameter->cis_row_id_product = $this->product->cis_row_id;
        $this->newParameterText = null;
        $parameter->save();
    }

    public function delete(ProductParameter $parameter) {
        if($parameter->cis_row_id_product = $this->product->cis_row_id) {
            $parameter->delete();
        }
    }
}
