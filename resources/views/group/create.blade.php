@extends('layout.app')

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Neue Gruppe</h2>

        <form action="{{ route('group.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name') }}" placeholder="z.B. Abteilung Technik" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="cis-input w-full" rows="2"
                          placeholder="Kurze Beschreibung der Gruppe">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="cis-label" for="color">Farbe</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="color" name="color" class="h-9 w-16 rounded border border-gray-300 cursor-pointer"
                           value="{{ old('color', '#3B82F6') }}">
                    <span class="text-sm text-gray-500">Wird in der Übersicht zur Unterscheidung angezeigt</span>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Gruppe erstellen</button>
                <a href="{{ route('group.index') }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
