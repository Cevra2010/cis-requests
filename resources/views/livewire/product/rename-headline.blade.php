<div class="mb-4">
    @if($rename)
    <input type="text" wire:keydown.enter='submitRename' wire:model.debounce='productNameInput' class="p-3 rounded border w-1/2 shadow-inner text-xl font-bold">
    <div class="flex space-x-4">
        <button class="cis-submit" wire:click='submitRename'>Speichern</button>     
        <button class="cis-abort" wire:click='abort'>Abbrechen</button>    
    </div>
    @else
    <h1 class="cis-headline mb-0">Produkt bearbeiten: {{ $product->name }}
    @endif
    @if(!$rename)
        <a href="#rename" class="text-sm ml-4 text-slate-600 font-light underline decoration-dotted underline-offset-2" wire:click='openRename'><i class="fa fa-edit"></i> Umbenennen</a>
    @endif
    </h1>
    @if($parent)
    <p>Unterprodukt von [<a href="{{ route("product.edit",$parent) }}">{{ $parent->name }}</a>]</p>
    @endif
</div>
