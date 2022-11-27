<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() {
        return view("product.index");
    }

    public function edit(Product $product) {
        if($product->hasParent()) {
            $parent = $product->getParent();
        }
        else {
            $parent = null;
        }
        return view("product.edit",[
            'product' => $product,
            'parent' => $parent,
        ]);
    }

    public function storePrice(Request $request,Product $product)
    {
        $this->validate($request,[
            'price' => 'required|nullable',
        ]);

        $price = $request->get('price');

        if(!is_numeric(str_replace(",",".",$price))) {
            return redirect()->back()->withErrors(['price' => 'Bitte eine Zahl angeben.']);
        }

        $product->newPrice($price);

        return redirect()->route("product.edit",$product);
    }

    public function create($parentProduct = null)
    {
        if($parentProduct) {
            $parent = Product::find($parentProduct);
        }
        else {
            $parent = null;
        }

        return view("product.create",[
            'parent' => $parent,
        ]);
    }

    public function store(StoreProductRequest $request) {
        $product = new Product();
        if($request->has("parent")) {
            $product->parent = $request->get('parent');
        }
        $product->name = $request->get('name');
        $product->save();
        if($request->get('parent')) {}
        return redirect()->route("product.edit",$product);
    }

    public function source() {

    }

    public function price() {
        return view("product.price");
    }
}
