@extends('layout.app')

@section('title', $source->name)

@section('header_actions')
    <a href="{{ route('source.delete', $source) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="max-w-xl">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Produktquelle bearbeiten</h2>

        <form action="{{ route('source.update', $source) }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name', $source->name) }}" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="url">Website</label>
                <input type="url" id="url" name="url" class="cis-input w-full"
                       value="{{ old('url', $source->url) }}" placeholder="https://www.beispiel.de">
                @error('url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Ansprechpartner</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="cis-label" for="contact_name">Name</label>
                        <input type="text" id="contact_name" name="contact_name" class="cis-input w-full"
                               value="{{ old('contact_name', $source->contact_name) }}">
                    </div>
                    <div>
                        <label class="cis-label" for="contact_phone">Telefon</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="cis-input w-full"
                               value="{{ old('contact_phone', $source->contact_phone) }}">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="cis-label" for="contact_email">E-Mail</label>
                    <input type="email" id="contact_email" name="contact_email" class="cis-input w-full"
                           value="{{ old('contact_email', $source->contact_email) }}">
                    @error('contact_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="cis-label" for="notes">Notizen</label>
                <textarea id="notes" name="notes" class="cis-input w-full" rows="3">{{ old('notes', $source->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('source') }}" class="btn btn-ghost">Zurück</a>
            </div>
        </form>
    </div>
</div>
@endsection
