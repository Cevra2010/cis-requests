<div>
    @if($started)    
        @include("layout.error_success")
        <div class="flex space-x-10">
            <div class="w-1/2">
                <h2 class="text-xl text-slate-600 mb-4">Lieferant</h2>
                <hr>
                <p class="font-light mb-4 mt-3">
                    Bitte wählen Sie den Lieferanten, zu dem Sie Preise anpassen oder einpflegen möchten.
                </p>  
                @if(!$productSource)
                    @foreach($productSources as $source)
                        <div class="p-2 bg-slate-200 border border-slate-300 hover:bg-slate-300 rounded shadow mb-2 cursor-pointer" wire:click='selectSource("{{ $source->cis_row_id }}")'>
                            {{ $source->name }}
                        </div>
                    @endforeach
                @else
                    <div class="flex items-center justify-center">
                        <div class="bg-red-200 h-12 text-red-700 flex items-center justify-center px-4 cursor-pointer" wire:click='resetSource'>
                            <i class="fa fa-circle-xmark"></i>
                        </div>
                        <div class="h-12 flex items-center w-full bg-slate-200 px-2">
                            {{ $productSource->name }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="w-1/2">
                @if($productSource) 
                    <h2 class="text-xl text-slate-600 mb-4">Produkt</h2>
                    <hr>
                    <p class="font-light mb-4 mt-3">
                        Bitte wählen Sie das Produkt, dessen Preis sie einpflegen oder aktualiseren möchte
                    </p>  
                    @if($product)
                        <div class="flex items-center justify-center">
                            <div class="bg-red-200 h-12 text-red-700 flex items-center justify-center px-4 cursor-pointer" wire:click='resetProduct'>
                                <i class="fa fa-circle-xmark"></i>
                            </div>
                            <div class="h-12 flex items-center w-full bg-slate-200 px-2">
                                {{ $product->name }}
                            </div>
                        </div>
                    @else
                        <div class="cis-form-group">
                            <input type="text" id="product" wire:model="productSearchName" wire:keydown.arrow-down='down' wire:keydown.arrow-up='up' wire:keydown.enter='selectArrowProduct'  placeholder="Suchen..." autofocus>
                        </div>
                        <div>
                            @if($productSearchResult)
                                @foreach($productSearchResult as $products)
                                    <div class="p-2  mb-2 cursor-pointer rounded @if($loop->iteration == $selectedProductResult) bg-slate-300 @else bg-slate-200 @endif" wire:click='selectProduct("{{ $products->cis_row_id }}")'>
                                        {{ $products->name }}
                                    </div>
                                @endforeach
                            @endif
                        </div>        
                    @endif
                @endif
            </div>
        </div>
        <div>
            @if($product)
                <h2 class="text-xl text-slate-600 mb-4 mt-10">Preis</h2>
                <hr>
                <p class="font-light mb-4 mt-3">
                    Bitte legen Sie einen neuen Preis fest.
                </p>  
                <div class="cis-form-group">
                    <input type="text" id="price" wire:model.debounce="price" wire:keydown.enter='submitPrice' placeholder="12,99" autofocus>
                </div>

                <button type="button" class="cis-submit" wire:click='submitPrice'>Preis einpflegen</button>
            @endif
        </div>
    @else
        <div class="flex w-full">
            <div class="bg-gradient-to-tr from-blue-800 to-teal-600 h-40 px-10 ring-offset-2 hover:ring-4 rounded-xl text-slate-200 flex-col flex items-center justify-center cursor-pointer" wire:click='start'>
                <i class="fa fa-rocket" style="font-size: 4em;"></i>
                <p class="mt-3">Massenpflege starten</p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
   window.livewire.on('focusme', function (focusElement) {
        document.getElementById(focusElement).focus();
    });

</script>
@endpush
