@extends("layout.app")

@section("content")

@section("title","Rolle löschen")
<a href="{{ route("user.role.edit",$role) }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Rolle löschen: {{ $role->name }}</h1>

@include("layout.error_success")
<form action="{{ route("user.role.edit.delete.store",$role) }}" method="POST">
    @csrf
    <div class="flex items-center space-x-5 text-slate-700 mt-4">
        <i class="fa fa-info-circle text-2xl"></i>
        <p>
            Um eine Rolle zu löschen, muss dies mit einer Sicherheitsabfrage bestätigt werden.<br>
            Tippen Sie in folgends Feld:
        </p>
    </div>

    @if($users->count())
    <div class="text-red-700 p-2 bg-slate-100 rounded mt-4">
        Diese Rolle kann nicht gelöscht werden, da Konten dieser Rolle zugeteilt sind:
        <ul class="list-disc">
            @foreach($users as $user)
                <li class="ml-8">{{ $user->name() }}</li>
            @endforeach
        </ul>
    </div>
    @else

    <p class="bg-slate-100 p-2 rounded mt-4 mb-4" style="font-family: 'Courier New', Courier, monospace;">
        DEL-{{ $role->name }}
    </p>
    <div class="cis-form-group">
        <label for="delete_key">Sicherheitsabfrage</label>
        <input type="text" name="delete_key" value="{{ old("delete_key") }}" @error("delete_key") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit bg-red-700 hover:bg-red-800">Rolle löschen</button>
</form>
@endif

@endsection
