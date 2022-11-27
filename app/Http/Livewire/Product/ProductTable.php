<?php

namespace App\Http\Livewire\Product;

use App\Models\Product;
use Livewire\Component;

class ProductTable extends Component
{
    public $searchString;
    public $orderBy = 'name';
    public $orderDirection = 'ASC';
    protected $queryString = [
        'searchString',
    ];

    public function render()
    {
        if($this->searchString)
        {
            $products = Product::where('name','like','%'.$this->searchString.'%')->with('prices')->orderBy($this->orderBy,$this->orderDirection)->get();
        }
        else {
            $products = Product::where('name','like','%'.$this->searchString.'%')->where('parent',null)->with('prices')->orderBy($this->orderBy,$this->orderDirection)->get();
        }
        return view('livewire.product.product-table',[
            'products' => $products,
        ]);
    }

    public function order($orderName) {
        if($orderName == $this->orderBy) {
            if($this->orderDirection == "ASC") {
                $this->orderDirection = "DESC";
            }
            else {
                $this->orderDirection = "ASC";
            }
        }
        else {
            $this->orderDirection = "ASC";
            $this->orderBy = $orderName;
        }
    }
}
