@extends("layout.app")

@section("content")

@section("title","Produktübersicht")

<h1 class="cis-headline">Produktübersicht</h1>

<div class="flex space-x-3 mb-4">
    <a href="{{ route("product.create") }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-circle-plus"></i>
            <p class="text-xs">Produkt erstellen</p>
        </div>
    </a>
    @access("product.source")
    <a href="{{ route("product.source.index") }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-truck"></i>
            <p class="text-xs">Produktquellen</p>
        </div>
    </a>
    @endaccess
    @access("product.edit.price")
    <a href="{{ route("product.price") }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-money"></i>
            <p class="text-xs">Produktpreise</p>
        </div>
    </a>
    @endaccess
</div>
@include("layout.error_success")
@livewire("product.product-table")

@endsection
