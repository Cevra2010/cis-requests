@extends('layout.app')

@section('title', $property->name)

@section('header_actions')
    <a href="{{ route('property.delete', $property) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="cis-card">
        <form action="{{ route('property.update', $property) }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name', $property->name) }}" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="description">Standard-Beschreibungstext</label>
                <p class="text-xs text-gray-400 mb-1">Vorlage für die Ausschreibung. Pro Projekt überschreibbar.</p>
                <textarea id="description" name="description" class="cis-input w-full" rows="5">{{ old('description', $property->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('property.index') }}" class="btn btn-ghost">Zurück zur Liste</a>
            </div>
        </form>
    </div>
</div>
@endsection
