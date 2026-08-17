@extends('layout.app')

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Neue Kategorie</h2>

        <form action="{{ route('category.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="cis-label" for="type">Typ <span class="text-red-500">*</span></label>
                <select id="type" name="type" class="cis-input w-full" required>
                    @foreach($types as $typeKey => $typeMeta)
                        <option value="{{ $typeKey }}" {{ $activeType === $typeKey ? 'selected' : '' }}>
                            {{ $typeMeta['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name') }}" placeholder="z.B. Löschfahrzeug" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="cis-input w-full" rows="2">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center gap-6">
                <div>
                    <label class="cis-label" for="color">Farbe</label>
                    <input type="color" id="color" name="color"
                           class="h-9 w-16 rounded border border-gray-300 cursor-pointer"
                           value="{{ old('color', '#3B82F6') }}">
                </div>
                <div class="flex-1">
                    <label class="cis-label" for="sort_order">Reihenfolge</label>
                    <input type="number" id="sort_order" name="sort_order" class="cis-input w-24"
                           value="{{ old('sort_order', 0) }}" min="0">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Kategorie erstellen</button>
                <a href="{{ route('category.index', ['type' => $activeType]) }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
