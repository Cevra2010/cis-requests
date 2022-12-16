@extends("layout.app")

@section("content")

@section("title","Projekt anlegen")
<a href="{{ route("project") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Projekt anlegen</h1>

@include("layout.error_success")
<form action="{{ route("project.store") }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="name">Projekt-Name*</label>
        <input type="text" name="name" value="{{ old("name") }}" @error("name") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="name">Beschreibung</label>
        <textarea rows="10" name="description" @error("description") class="is-invalid" @endif>{{ old("name") }}</textarea>
    </div>
    <p class="text-sm font-light">*Pflichtfelder</p>   
    <button type="submit" class="cis-submit">Projekt anlegen</button>
</form>

@endsection
