<?php

namespace App\Http\Livewire\Product;

use App\Models\Product;
use App\Models\ProductDescription;
use Livewire\Component;
use Symfony\Component\Console\Descriptor\Descriptor;

class ProductDescriptionEditor extends Component
{
    public $product;
    public $description;
    public $newDescription = null;
    public $descriptionDirty = false;

    public function mount(Product $product) {
        $this->product = $product;
        if($this->description = $this->product->description()){
            $this->newDescription = $this->description->text;
        }
        else {
            $this->description = new ProductDescription();
        }
    }
    public function render()
    {
        return view('livewire.product.product-description-editor');
    }

    public function store() {
        if(!$this->description || $this->descriptionDirty) {
            $newProductDescriptionObject = new ProductDescription();
            $newProductDescriptionObject->cis_row_id_product = $this->product->cis_row_id;
            $newProductDescriptionObject->text = $this->newDescription;
            if($this->description) {
                $this->description->delete();
            }
            $newProductDescriptionObject->save();
            $this->description = $newProductDescriptionObject;
            $this->newDescription = $this->description->text;
            $this->descriptionDirty = false;
            session()->flash('success','Beschreibung wurde gespeichert.');
        }
    }

    public function updatedNewDescription() {
        if($this->newDescription != $this->description->text) {
            $this->descriptionDirty = true;
        }
    }
}
