@extends("layout.app")

@section("content")

@section("title","Konto löschen")
<a href="{{ route("user.edit",$user) }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Konto löschen: {{ $user->name() }}</h1>

@include("layout.error_success")
<form action="{{ route("user.edit.delete.store",$user) }}" method="POST">
    @csrf
    <div class="flex items-center space-x-5 text-slate-700 mt-4">
        <i class="fa fa-info-circle text-2xl"></i>
        <p>
            Um ein Konto zu löschen, muss dies mit einer Sicherheitsabfrage bestätigt werden.<br>
            Tippen Sie in folgends Feld:
        </p>
    </div>

    <p class="bg-slate-100 p-2 rounded mt-4 mb-4" style="font-family: 'Courier New', Courier, monospace;">
        DEL-{{ $user->firstname }}/{{ $user->lastname }}
    </p>
    <div class="cis-form-group">
        <label for="delete_key">Sicherheitsabfrage</label>
        <input type="text" name="delete_key" value="{{ old("delete_key") }}" @error("delete_key") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit bg-red-700 hover:bg-red-800">Konto löschen</button>
</form>

@endsection
