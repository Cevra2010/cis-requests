<?php

namespace App\Http\Controllers\Source;

use App\Http\Controllers\Controller;
use App\Http\Requests\Source\StoreSourceRequest;
use App\Http\Requests\Source\UpdateSourceRequest;
use App\Models\ProductSource;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function index() {
        return view("source.index");
    }

    public function create() {
        return view("source.create");
    }    

    public function store(StoreSourceRequest $request) {
        $source = new ProductSource();
        $source->fill($request->all());
        $source->save();
        session()->flash('success','Produktquelle wurde hinzugefügt.');
        return redirect()->route("source");
    }

    public function edit(ProductSource $source) {
        return view("source.edit",[
            'source' => $source,
        ]);
    }

    public function update(UpdateSourceRequest $request,ProductSource $source) {
        $source->fill($request->all());
        $source->save();
        session()->flash('success','Produktquelle wurde geändert.');
        return redirect()->route("source");
    }
}
