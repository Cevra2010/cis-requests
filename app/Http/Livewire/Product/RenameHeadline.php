<?php

namespace App\Http\Livewire\Product;

use App\Http\Logic\CisAccess\Facades\Access;
use App\Models\Product;
use Livewire\Component;

class RenameHeadline extends Component
{

    public $product;
    public $parent = null;
    public $rename = false;
    public $productNameInput = null;

    public function render()
    {
        return view('livewire.product.rename-headline');
    }

    public function mount(Product $product)
    {
        $this->product = $product;
        $this->productNameInput = $product->name;
        if($this->product->hasParent()) {
            $this->parent = $product->getParent();
        }
    }

    public function openRename() {
        $this->rename = true;
    }

    public function submitRename() {
        if(Access::hasAccess("product.edit.rename")) {
            $this->product->name = $this->productNameInput;
            $this->product->save();
            $this->rename = false;  
        }
    }

    public function abort() {
        $this->productNameInput = $this->product->name;
        $this->rename = false;
    }
}
