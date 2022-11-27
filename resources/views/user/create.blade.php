@extends("layout.app")

@section("content")

@section("title","Konto erstellen")
<a href="{{ route("user") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Konto erstellen</h1>

@include("layout.error_success")
<form action="{{ route("user.store") }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="firstname">Vorname</label>
        <input type="text" name="firstname" value="{{ old("firstname") }}" @error("firstname") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="lastname">Nachname</label>
        <input type="text" name="lastname" value="{{ old("lastname") }}" @error("lastname") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="email">E-Mail</label>
        <input type="text" name="email" value="{{ old("email") }}" @error("email") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="password">Passwort</label>
        <input type="password" name="password" value="{{ old("password") }}" @error("password") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="password_confirmation">Passwort wiederholen</label>
        <input type="password" name="password_confirmation" value="{{ old("password_confirmation") }}" @error("password_confirmation") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit">Konto erstellen</button>
</form>

@endsection
