<div class="relative">
    @if($selectedProductObject)
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-50 border border-primary-200">
            <span class="flex-1 text-sm text-primary-800">{{ $selectedProductObject->name }}</span>
            <button type="button" wire:click="$set('selectedProductObject', null)" class="text-primary-400 hover:text-primary-700">
                <i class="fa fa-xmark text-xs"></i>
            </button>
        </div>
    @else
        <input type="text" wire:model="searchString"
               wire:keydown.arrow-down="down" wire:keydown.arrow-up="up" wire:keydown.enter="selectProduct"
               placeholder="Bestehendes Produkt suchen oder neuen Namen eingeben…"
               class="cis-input w-full text-sm" autocomplete="off">

        @if($products && $products->count())
        <ul class="absolute z-10 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
            @foreach($products as $product)
                <li wire:click="selectProduct('{{ $product->cis_row_id }}')"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 {{ $selectedProduct == $loop->iteration ? 'bg-primary-50' : '' }}">
                    <span class="text-gray-800">{{ $product->name }}</span>
                    @if($product->hasParent())
                        <span class="block text-[11px] text-gray-400">
                            bereits Unterprodukt von {{ $product->getParents()->pluck('name')->implode(', ') }} — kann mehrfach zugeordnet werden
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
        @endif
    @endif

    <button type="button" wire:click="submitForm" class="btn btn-primary btn-sm mt-2">
        <i class="fa fa-plus mr-1.5"></i> Als Unterprodukt hinzufügen
    </button>
    <p class="mt-1.5 text-xs text-gray-400">
        Wird ein bestehendes Produkt ausgewählt, kann es auch bereits Unterprodukt eines anderen Produkts sein
        (z.B. ein gemeinsames Übergangsstück).
    </p>
</div>
