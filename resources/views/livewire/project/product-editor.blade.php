<div>
    <div class="w-full flex space-x-4">
        <div class="w-1/3 bg-slate-200 rounded-t rounded-b shadow border border-slate-300">
            <div class="w-full bg-emerald-700 rounded-t p-4 text-white">verfügbare Produkte ({{ $products->count() }})</div>
            <div class=" h-80 overflow-y-scroll">
                <div class="px-6">
                    @foreach($products as $product)
                        @if(!$product->hasParent())
                        <div class="py-2 border-b border-b-slate-300 flex items-center">
                            <div>{{ $product->name }}</div>
                            <div class="bg-emerald-600 text-white p-2 rounded cursor-pointer ml-auto " wire:click='add("{{ $product->cis_row_id }}")'><i class="fa fa-plus"></i></div>
                        </div>
                            @foreach($products->where("parent",$product->cis_row_id) as $child)
                            <div class="py-2 border-b border-b-slate-300 flex items-center">
                                <div class="mx-2"><i class="fa fa-arrow-right"></i></div>
                                <div>{{ $child->name }}</div>
                                <div class="bg-emerald-600 text-white cursor-pointer p-2 rounded ml-auto " wire:click='add("{{ $child->cis_row_id }}")'><i class="fa fa-plus"></i></div>
                            </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div class="w-2/3 bg-slate-200 rounded-t rounded-b shadow border border-slate-300">
            <div class="w-full bg-blue-700 p-4 text-white rounded-t">zugeordnete Produkte ({{ count($linkedProducts) }}) </div>
            <div class=" h-80 overflow-y-scroll">
                <div class="">
                    @foreach($linkedProducts as $linkedProduct)
                        <div class="px-4 py-2 border-b hover:bg-slate-100 cursor-pointer" wire:click='selectProduct("{{ $linkedProduct->cis_row_id }}")'>
                            <p>{{ $linkedProduct->name }}</p>
                            <p class="text-sm font-light">Anzahl: {{ $linkedProduct->pivot->product_count }}</p>
                            @if($childs = $linkedProduct->getChild())
                                @foreach($childs as $child)
                                <p class="text-sm font-light ml-2">+ {{ $child->name }}</p>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @if($selectedProduct)
    <div class="bg-slate-200 rounded-t rounded-b mt-4">
        <div class="p-4 border-b border-slate-300 flex items-center">
            <div>{{ $selectedProduct->name }}</div>
            <div class="ml-auto">
                <a href="#del" class="cis-abort" wire:click="delete()">Produkt löschen</a>
            </div>
        </div>
        <div class="p-4">
            <form wire:submit.prevent='submitProduct'>
                <div class="cis-form-group">
                    <label for="product_count">Anzahl</label>
                    <input type="text" wire:model='selectedProduct.pivot.product_count'>
                </div>  
                <button type="submit" class="cis-submit">Speichern</button>
            </form>
        </div>
    </div>
    @endif
</div>
