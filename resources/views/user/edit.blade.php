@extends("layout.app")

@section("content")

@section("title","Konto bearbeiten")
<a href="{{ route("user") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Konto bearbeiten</h1>

<div class="flex space-x-3 mb-4">
    @access("user.edit.roles")
    <a href="{{ route("user.edit.roles",$user) }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-scale-balanced"></i>
            <p class="text-xs">Benutzerrolle/n vergeben</p>
        </div>
    </a>
    @endaccess
    <a href="{{ route("user.edit.security",$user) }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-key"></i>
            <p class="text-xs">Kontozugang</p>
        </div>
    </a>
    @access("user.edit.delete")
    <a href="{{ route("user.edit.delete",$user) }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-user-minus"></i>
            <p class="text-xs">Konto löschen</p>
        </div>
    </a>
    @endaccess
</div>
<hr class="mb-4">
<p class="text-xs text-slate-600 mb-4">Erstellt: {{ $user->created_at->format("d.m.Y H:i ")}} | letzte Änderung: {{ $user->updated_at->format("d.m.Y H:i") }}
@include("layout.error_success")
<form action="{{ route("user.edit.update",$user) }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="firstname">Vorname</label>
        <input type="text" name="firstname" value="{{ old("firstname",$user->firstname) }}" @error("firstname") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="lastname">Nachname</label>
        <input type="text" name="lastname" value="{{ old("lastname",$user->lastname) }}" @error("lastname") class="is-invalid" @endif>
    </div>
    <div class="cis-form-group">
        <label for="email">E-Mail</label>
        <input type="text" name="email" value="{{ old("email",$user->email) }}" @error("email") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit">Speichern</button>
</form>

@endsection
