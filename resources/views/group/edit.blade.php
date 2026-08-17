@extends('layout.app')

@section('header_actions')
    <a href="{{ route('group.permissions', $group) }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-shield-halved mr-1"></i> Berechtigungen
    </a>
    <a href="{{ route('group.delete', $group) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Gruppe bearbeiten</h2>

        <form action="{{ route('group.update', $group) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name', $group->name) }}" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="cis-input w-full" rows="2">{{ old('description', $group->description) }}</textarea>
            </div>

            <div>
                <label class="cis-label" for="color">Farbe</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="color" name="color" class="h-9 w-16 rounded border border-gray-300 cursor-pointer"
                           value="{{ old('color', $group->color ?? '#3B82F6') }}">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('group.index') }}" class="btn btn-ghost">Zurück</a>
            </div>
        </form>
    </div>

    {{-- Mitglieder --}}
    <div class="cis-card mt-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">
            Mitglieder <span class="text-gray-400 font-normal">({{ $group->users->count() }})</span>
        </h3>
        @if($group->users->isNotEmpty())
        <div class="divide-y divide-gray-100">
            @foreach($group->users as $member)
            <div class="flex items-center justify-between py-2">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $member->name() }}</p>
                    <p class="text-xs text-gray-500">{{ $member->email }}</p>
                </div>
                <a href="{{ route('user.edit', $member) }}" class="btn btn-ghost btn-sm">
                    <i class="fa fa-arrow-right"></i>
                </a>
            </div>
            @endforeach
        </div>
        @else
            <p class="text-sm text-gray-400">Keine Mitglieder zugewiesen. Mitglieder werden über die Benutzerverwaltung zugeordnet.</p>
        @endif
    </div>
</div>
@endsection
