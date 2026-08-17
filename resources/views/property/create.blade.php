@extends('layout.app')

@section('title', 'Neue Eigenschaft')

@section('content')
<div class="max-w-2xl">
    <div class="cis-card">
        <form action="{{ route('property.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name') }}" placeholder="z.B. Geländetauglich" required autofocus>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="description">Standard-Beschreibungstext</label>
                <p class="text-xs text-gray-400 mb-1">Dieser Text wird als Vorlage für die Ausschreibung verwendet. Pro Projekt kann er überschrieben werden.</p>
                <textarea id="description" name="description" class="cis-input w-full" rows="5"
                          placeholder="Das Fahrzeug muss über ein geländetaugliches Fahrwerk verfügen…">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Anlegen</button>
                <a href="{{ route('property.index') }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
