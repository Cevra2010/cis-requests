@extends("layout.app")

@section("content")

@section("title","Produkt erstellen")
@if($parent)
<a href="{{ route("product.edit",$parent) }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
@else
<a href="{{ route("product") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
@endif
<h1 class="cis-headline">Produkt erstellen</h1>

@include("layout.error_success")
<form action="{{ route("product.store") }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="name">Name</label>
        <input type="text" name="name" value="{{ old("name") }}" @error("name") class="is-invalid" @endif>
    </div>
    @if($parent)
        <input type="hidden" name="parent" value="{{ $parent->cis_row_id }}">
    @endif
    <button type="submit" class="cis-submit">Produkt erstellen</button>
</form>

@endsection
