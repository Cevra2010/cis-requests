@extends('layout.app')

@section('title', 'Neue Produktquelle')

@section('content')
<div class="max-w-xl">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Neue Produktquelle anlegen</h2>

        <form action="{{ route('source.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="cis-label" for="name">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name') }}" required placeholder="z.B. Rosenbauer Deutschland GmbH">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="url">Website</label>
                <input type="url" id="url" name="url" class="cis-input w-full"
                       value="{{ old('url') }}" placeholder="https://www.beispiel.de">
                @error('url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Ansprechpartner</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="cis-label" for="contact_name">Name</label>
                        <input type="text" id="contact_name" name="contact_name" class="cis-input w-full"
                               value="{{ old('contact_name') }}" placeholder="Max Mustermann">
                    </div>
                    <div>
                        <label class="cis-label" for="contact_phone">Telefon</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="cis-input w-full"
                               value="{{ old('contact_phone') }}" placeholder="+49 123 456789">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="cis-label" for="contact_email">E-Mail</label>
                    <input type="email" id="contact_email" name="contact_email" class="cis-input w-full"
                           value="{{ old('contact_email') }}" placeholder="kontakt@beispiel.de">
                    @error('contact_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="cis-label" for="notes">Notizen</label>
                <textarea id="notes" name="notes" class="cis-input w-full" rows="3"
                          placeholder="Interne Notizen, Konditionen, Anmerkungen…">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Quelle erstellen</button>
                <a href="{{ route('source') }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
