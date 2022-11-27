@extends("layout.app")

@section("content")

@section("title","Konto erstellen")
<a href="{{ route("user.role.index") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Rolle erstellen</h1>

@include("layout.error_success")
<form action="{{ route("user.role.store") }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="name">Name der Rolle</label>
        <input type="text" name="name" value="{{ old("name") }}" @error("name") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit">Rolle erstellen</button>
</form>

@endsection
