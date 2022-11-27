@extends("layout.app")

@section("content")

@section("title","Produkt bearbeiten")
<a href="{{ route("product") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
@if($parent)
<h1 class="cis-headline">Produkt bearbeiten: [<a href="{{ route("product.edit",$parent) }}">{{ $parent->name }}</a>] => {{ $product->name }}</h1>
@else
<h1 class="cis-headline">Produkt bearbeiten: {{ $product->name }}</h1>
@endif
<div class="flex space-x-3 mb-4">
    @access("product.create")
    @if(!$product->parent)
    <a href="{{ route("product.create",$product->cis_row_id) }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-circle-plus"></i>
            <p class="text-xs">Unterprodukt erstellen</p>
        </div>
    </a>
    @else
    <a href="{{ route("product.create",$product->getParent()->cis_row_id) }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-40 p-3 rounded text-center">
            <i class="fa fa-circle-plus"></i>
            <p class="text-xs">weiteres Unterprodukt erstellen für [{{ $product->getParent()->name }}]</p>
        </div>
    </a>
    @endif
    @endaccess
    @access("product.edit.delete")
    <a href="{{ route("product.edit.delete",$product) }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-40  p-3 rounded text-center">
            <i class="fa fa-trash"></i>
            <p class="text-xs">Produkt löschen</p>
        </div>
    </a>
    @endaccess
</div>
<p class="text-xs text-slate-600 mb-4">Erstellt: {{ $product->created_at->format("d.m.Y H:i ")}} | letzte Änderung: {{ $product->updated_at->format("d.m.Y H:i") }}
@include("layout.error_success")

<div class="grid grid-cols-4 gap-4">
    <div class="p-4 bg-slate-100 rounded shadow text-center border">
        @if($product->hasChild() && $product->price() && $product->price()->amount == 0)
            <p class="text-sm">Produktpreis (Sammelpreis)</p>
            <p class="text-3xl mt-4">{{ $product->getGroupPriceForHumans() }}</p>
        @else
            <p class="text-sm">Produktpreis</p>
            @if($product->prices()->count())
            <p class="text-3xl mt-4">{{ $product->prices()->orderBy('created_at','DESC')->first()->amountForHumans() }}</p>
            <p class="text-xs mt-4 text-slate-700">{{ $product->prices()->orderBy('created_at','DESC')->first()->created_at->format("d.m.Y H:i") }}</p>
            @else
                <p class="text-3xl mt-4">kein Eintrag</p>
            @endif
            @if($product->hasChild())
                <div class="mt-2">Sammelpreis: {{ $product->getGroupPriceForHumans() }}</div>
            @endif
        @endif
    </div>
</div>

<div class="flex w-full space-x-4 mt-4">
    <div class="w-2/3 border rounded p-6 bg-slate-100 shadow text-slate-500">
        <div class="text-2xl text-slate-700 mb-4">Produktbeschreibung</div>

        @access("product.edit.description")
            @livewire("product.product-description-editor",['product' => $product])
        @else
            @if($product->description())
                {!! nl2br($product->description()->text) !!}
            @else
            <p class="text-slate-500">- keine Produktbeschreibung vorhanden -</p>
            @endif
        @endaccess
    </div>
    <div class="w-1/3 border rounded p-6 bg-slate-100 shadow text-slate-500">
        <div class="text-2xl text-slate-700 mb-4">Produktparameter</div>

        @access("product.edit.parameter")
            @livewire("product.product-parameter-editor",['product' => $product])
        @else
        @if($product->parameters()->count())
        <ul>
            @foreach($product->parameters()->get() as $parameter)
                <li class="p-2 border-b">{{ $parameter->text }}</li>
            @endforeach
        </ul>
        @else
            <p class="text-slate-500">- keine Produktparameter vorhanden -</p>
        @endif
        @endaccess
    </div>
</div>

@if($product->hasChild())
<h1 class="text-2xl text-slate-700 mt-8 mb-2">Unterprodukte</h1>
<div>
    <div class="cis-table mt-3">
        <table>
            <thead>
                <tr>
                   <th>Name</th>
                   <th>Preis</th>
                   <th>letzte Änderung</th>
                </tr>
            </thead>
            <tbody>
                @foreach($product->getChild()->sortBy('name') as $child)
                <tr class="cursor-pointer" onclick='location.href="{{ route("product.edit",$child) }}"'>
                    <td>{{ $child->name }}</td>
                    <td>
                        @if($child->price())
                            {{$child->price()->amountForHumans()}}
                        @else
                            - nicht festgelegt -
                        @endif
                    </td>
                    <td>{{ $child->updated_at->format("d.m.Y H:i") }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<h1 class="text-2xl text-slate-700 mt-8 mb-2">Preisverlauf</h1>
<div>
    <div class="cis-table mt-3">
        <table>
            <thead>
                <tr>
                   <th>Datum</th>
                   <th>Uhrzeit</th>
                   <th>Preis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($product->prices()->orderBy('created_at','DESC')->get() as $price)
                <tr>
                    <td>{{ $price->created_at->format("d.m.Y") }}</td>
                    <td>{{ $price->created_at->format("H:i") }}</td>
                    <td>{{ $price->amountForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


@endsection
