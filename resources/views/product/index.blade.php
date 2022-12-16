@extends("layout.app")

@section("content")

@section("title","Produktübersicht")

<h1 class="cis-headline">Produktübersicht</h1>

<div class="flex space-x-3 mb-4">
    <a href="{{ route("product.create") }}">
        <div class="btn-add w-min p-3 text-center">
            <i class="fa fa-circle-plus"></i>
            <p class="text-xs">Produkt erstellen</p>
        </div>
    </a>
    @access("source")
    <a href="{{ route("source") }}">
        <div class="btn-nav w-min p-3 text-center">
            <i class="fa fa-truck"></i>
            <p class="text-xs">Produktquellenverwaltung</p>
        </div>
    </a>
    @endaccess
</div>
@include("layout.error_success")
@livewire("product.product-table")

@endsection
