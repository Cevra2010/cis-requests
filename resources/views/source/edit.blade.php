@extends("layout.app")

@section("content")

@section("title","Produktquelle bearbeiten")
<a href="{{ route("source") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Produktquelle bearbeiten</h1>

@include("layout.error_success")
<form action="{{ route("source.edit",$source) }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="name">Name</label>
        <input type="text" name="name" value="{{ old("name",$source->name) }}" @error("name") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit">Produkt speichern</button>
</form>

@endsection
