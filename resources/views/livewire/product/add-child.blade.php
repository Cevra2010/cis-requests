<div>
    <div class="cis-box">
        <div>Bestehendes Produkt hinzufügen</div>
        <div>
            <div class="cis-form-group">
                <label for="searchString">Produkt suchen</label>
                @if($selectedProductObject)
                    <div class="cis-fake-input">
                        <div><i class="fa fa-trash"></i></div>
                        <div>{{ $selectedProductObject->name }}</div>
                    </div>
                @else
                    <input type="text" wire:model='searchString' wire:keydown.arrow-down='down' wire:keydown.arrow-up='up' wire:keydown.enter='selectProduct'>
                @endif
                @if($products)
                    <ul class="cis-form-select">
                        @foreach($products as $product)
                            <li class="@if($selectedProduct == $loop->iteration) selected @endif" wire:click='selectProduct("{{ $product->cis_row_id }}")'>{{ $product->name }}</li>
                        @endforeach
                    </ul>
                    @if(!$products->count())
                       
                    @endif
                @endif
                <button type="button" class="cis-submit" wire:click='submitForm'>Unterprodukt hinzufügen</button>
            </div>
        </div>
    </div>
</div>
