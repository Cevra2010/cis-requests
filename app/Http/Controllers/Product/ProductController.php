<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        return view('product.index');
    }

    public function create($parentProduct = null)
    {
        $parent = $parentProduct ? Product::where('cis_row_id', $parentProduct)->firstOrFail() : null;
        return view('product.create', compact('parent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);

        $product = Product::create(['name' => $data['name']]);

        // Eltern-Kind-Beziehung über Pivot-Tabelle
        if ($request->filled('parent')) {
            $parent = Product::where('cis_row_id', $request->get('parent'))->first();
            if ($parent) {
                DB::table('product_child')->insert([
                    'cis_row_id_parent' => $parent->cis_row_id,
                    'cis_row_id_child'  => $product->cis_row_id,
                ]);
            }
        }

        return redirect()->route('product.edit', $product);
    }

    public function edit(Product $product)
    {
        $prices  = $product->prices()->with('source')->orderByDesc('created_at')->get();
        $sources = ProductSource::orderBy('name')->get();

        return view('product.edit', compact('product', 'prices', 'sources'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $product->update($data);

        session()->flash('success', 'Produkt wurde umbenannt.');
        return redirect()->route('product.edit', $product);
    }

    public function storePrice(Request $request, Product $product)
    {
        $data = $request->validate([
            'price'     => 'required',
            'source_id' => 'required|string',
        ]);

        $source = ProductSource::where('cis_row_id', $data['source_id'])->firstOrFail();
        Price::add($data['price'], $product, $source);

        session()->flash('success', 'Preis wurde eingetragen.');
        return redirect()->route('product.edit', $product);
    }

    public function delete(Product $product)
    {
        $childCount = $product->childs()->count();
        return view('product.delete', compact('product', 'childCount'));
    }

    public function deleteStore(Product $product)
    {
        // Aus allen Pivot-Einträgen entfernen
        DB::table('product_child')
            ->where('cis_row_id_parent', $product->cis_row_id)
            ->orWhere('cis_row_id_child', $product->cis_row_id)
            ->delete();

        $product->delete();

        session()->flash('success', 'Produkt "' . $product->name . '" wurde gelöscht.');
        return redirect()->route('product');
    }

    public function price()
    {
        return view('product.price');
    }
}
