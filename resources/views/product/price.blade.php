@extends("layout.app")

@section("content")

@section("title","Produktpreise verwalten")
<a href="{{ route("product") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Produktpreise verwalten</h1>

@livewire("product.price-editor")

@endsection
