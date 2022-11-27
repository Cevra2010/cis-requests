@extends("layout.app")

@section("content")

@section("title","Kontozugang bearbeiten")
<a href="{{ route("user.edit",$user) }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Kontozugang bearbeiten: {{ $user->name() }}</h1>
<div class="flex items-center text-slate-700 my-4">
    <i class="fa fa-info-circle text-2xl"></i>
    <div class="ml-12">
        <p class="text-sm">Bitte beachten Sie bei der Vergabe eines neues Passwortes folgende Passwortrichtlinien</p>
        <ul class="list-disc mb-4 text-sm">
            <li class="ml-6">Mindestens 8 Zeichen</li>
            <li class="ml-6">Maximal 52 Zeichen</li>
            <li class="ml-6">Mindestens eine Zahl</li>
        </ul>
    </div>
</div>
@include("layout.error_success")
<form action="{{ route("user.edit.security.update",$user) }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="password">Passwort</label>
        <input type="password" name="password" value="{{ old("password") }}" @error("password") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="password_confirmation">Passwort wiederholen</label>
        <input type="password" name="password_confirmation" value="{{ old("password_confirmation") }}" @error("password_confirmation") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit">Passwort jetzt ändern</button>
</form>

@endsection
