@extends('layout.app')

@section('header_actions')
    <a href="{{ route('category.delete', $category->id) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-1">Kategorie bearbeiten</h2>
        <p class="text-xs text-gray-400 mb-4">Typ: {{ \CisFoundation\CisCategoryManager\CisCategoryManager::getTypeLabel($category->type) }}</p>

        <form action="{{ route('category.update', $category->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name', $category->name) }}" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="cis-input w-full" rows="2">{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="flex items-center gap-6">
                <div>
                    <label class="cis-label" for="color">Farbe</label>
                    <input type="color" id="color" name="color"
                           class="h-9 w-16 rounded border border-gray-300 cursor-pointer"
                           value="{{ old('color', $category->color ?? '#3B82F6') }}">
                </div>
                <div class="flex-1">
                    <label class="cis-label" for="sort_order">Reihenfolge</label>
                    <input type="number" id="sort_order" name="sort_order" class="cis-input w-24"
                           value="{{ old('sort_order', $category->sort_order) }}" min="0">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('category.index', ['type' => $category->type]) }}" class="btn btn-ghost">Zurück</a>
            </div>
        </form>
    </div>
</div>
@endsection
