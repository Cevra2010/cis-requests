@extends('layout.app')

@section('content')
<div class="max-w-md">
    <div class="cis-card border border-red-200">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fa fa-triangle-exclamation text-red-600"></i>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Rolle löschen</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Die Rolle <strong>{{ $role->name }}</strong> wird dauerhaft gelöscht.
                </p>
            </div>
        </div>

        @if($users->count())
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                <p class="text-sm font-medium text-amber-800 mb-2">
                    <i class="fa fa-triangle-exclamation mr-1"></i>
                    {{ $users->count() }} Benutzer haben diese Rolle zugewiesen:
                </p>
                <ul class="text-sm text-amber-700 space-y-0.5">
                    @foreach($users as $u)
                        <li>· {{ $u->name() }}</li>
                    @endforeach
                </ul>
                <p class="text-xs text-amber-600 mt-2">Die Rolle wird trotzdem gelöscht. Die Benutzer verlieren die damit verbundenen Rechte.</p>
            </div>
        @endif

        <form action="{{ route('role.destroy', $role) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="cis-label" for="delete_key">
                    Zur Bestätigung <code class="bg-gray-100 px-1 rounded">DEL-{{ $role->name }}</code> eingeben:
                </label>
                <input type="text" id="delete_key" name="delete_key" class="cis-input w-full"
                       placeholder="DEL-{{ $role->name }}" autocomplete="off">
                @error('delete_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-danger">Rolle löschen</button>
                <a href="{{ route('role.index') }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
