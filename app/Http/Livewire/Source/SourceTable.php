<?php

namespace App\Http\Livewire\Source;

use Livewire\Component;
use App\Models\ProductSource;

class SourceTable extends Component
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
            $sources = ProductSource::where('name','like','%'.$this->searchString.'%')->orderBy($this->orderBy,$this->orderDirection)->get();
        }
        else {
            $sources = ProductSource::where('name','like','%'.$this->searchString.'%')->orderBy($this->orderBy,$this->orderDirection)->get();
        }
        return view('livewire.source.source-table',[
            'sources' => $sources,
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
