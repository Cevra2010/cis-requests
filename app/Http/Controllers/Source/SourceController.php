<?php

namespace App\Http\Controllers\Source;

use App\Http\Controllers\Controller;
use App\Models\ProductSource;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function index()
    {
        $sources = ProductSource::orderBy('name')->get();
        return view('source.index', compact('sources'));
    }

    public function create()
    {
        return view('source.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255|unique:product_sources,name',
            'url'           => 'nullable|url|max:500',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'notes'         => 'nullable|string|max:2000',
        ]);

        ProductSource::create($data);

        session()->flash('success', 'Produktquelle "' . $data['name'] . '" wurde erstellt.');
        return redirect()->route('source');
    }

    public function edit(ProductSource $source)
    {
        return view('source.edit', compact('source'));
    }

    public function update(Request $request, ProductSource $source)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255|unique:product_sources,name,' . $source->cis_row_id . ',cis_row_id',
            'url'           => 'nullable|url|max:500',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'notes'         => 'nullable|string|max:2000',
        ]);

        $source->update($data);

        session()->flash('success', 'Produktquelle wurde aktualisiert.');
        return redirect()->route('source');
    }

    public function delete(ProductSource $source)
    {
        $priceCount = $source->prices()->count();
        return view('source.delete', compact('source', 'priceCount'));
    }

    public function destroy(ProductSource $source)
    {
        $source->delete();

        session()->flash('success', 'Produktquelle "' . $source->name . '" wurde gelöscht.');
        return redirect()->route('source');
    }
}
