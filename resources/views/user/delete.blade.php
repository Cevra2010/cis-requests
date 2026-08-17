@extends('layout.app')

@section('content')
<div class="max-w-md">
    <div class="cis-card border border-red-200">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fa fa-triangle-exclamation text-red-600"></i>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Konto löschen</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Das Konto <strong>{{ $user->name() }}</strong> wird dauerhaft gelöscht.
                    Alle Gruppen-, Rollen- und Rechtezuweisungen werden entfernt.
                </p>
            </div>
        </div>

        <form action="{{ route('user.destroy', $user) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="cis-label" for="delete_key">
                    Zur Bestätigung <code class="bg-gray-100 px-1 rounded">DEL-{{ $user->firstname }}/{{ $user->lastname }}</code> eingeben:
                </label>
                <input type="text" id="delete_key" name="delete_key" class="cis-input w-full"
                       autocomplete="off">
                @error('delete_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-danger">Konto löschen</button>
                <a href="{{ route('user.edit', $user) }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
