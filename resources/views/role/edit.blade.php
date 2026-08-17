@extends('layout.app')

@section('header_actions')
    <a href="{{ route('role.permissions', $role) }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-shield-halved mr-1"></i> Berechtigungen
    </a>
    <a href="{{ route('role.delete', $role) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <div class="flex items-center gap-2 mb-4">
            @if($role->color)
                <span class="w-4 h-4 rounded-full" style="background: {{ $role->color }}"></span>
            @endif
            <h2 class="text-base font-semibold text-gray-900">{{ $role->name }}</h2>
        </div>

        <form action="{{ route('role.update', $role) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name', $role->name) }}" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="cis-input w-full" rows="2">{{ old('description', $role->description) }}</textarea>
            </div>

            <div>
                <label class="cis-label" for="color">Farbe</label>
                <input type="color" id="color" name="color" class="h-9 w-16 rounded border border-gray-300 cursor-pointer"
                       value="{{ old('color', $role->color ?? '#8B5CF6') }}">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('role.index') }}" class="btn btn-ghost">Zurück</a>
            </div>
        </form>
    </div>

    <div class="cis-card mt-4">
        <p class="text-xs text-gray-400">
            {{ $role->users()->count() }} Benutzer · Erstellt {{ $role->created_at->format('d.m.Y') }} · Geändert {{ $role->updated_at->format('d.m.Y') }}
        </p>
    </div>
</div>
@endsection
