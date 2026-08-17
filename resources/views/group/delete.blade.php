@extends('layout.app')

@section('content')
<div class="max-w-md">
    <div class="cis-card border border-red-200">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fa fa-triangle-exclamation text-red-600"></i>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Gruppe löschen</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Die Gruppe <strong>{{ $group->name }}</strong> wird dauerhaft gelöscht.
                    Alle zugewiesenen Berechtigungen werden entfernt. Benutzer verlieren die damit verbundenen Rechte.
                </p>
            </div>
        </div>

        <form action="{{ route('group.destroy', $group) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="cis-label" for="delete_key">
                    Zur Bestätigung <code class="bg-gray-100 px-1 rounded">DEL-{{ $group->name }}</code> eingeben:
                </label>
                <input type="text" id="delete_key" name="delete_key" class="cis-input w-full"
                       placeholder="DEL-{{ $group->name }}" autocomplete="off">
                @error('delete_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-danger">Gruppe löschen</button>
                <a href="{{ route('group.index') }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
