@extends('layout.app')

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Neues Konto erstellen</h2>

        <form action="{{ route('user.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="cis-label" for="firstname">Vorname <span class="text-red-500">*</span></label>
                    <input type="text" id="firstname" name="firstname" class="cis-input w-full"
                           value="{{ old('firstname') }}" required>
                    @error('firstname')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="lastname">Nachname <span class="text-red-500">*</span></label>
                    <input type="text" id="lastname" name="lastname" class="cis-input w-full"
                           value="{{ old('lastname') }}" required>
                    @error('lastname')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="cis-label" for="email">E-Mail <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" class="cis-input w-full"
                       value="{{ old('email') }}" required>
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="password">Passwort <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" class="cis-input w-full"
                       placeholder="Mindestens 8 Zeichen" required>
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="password_confirmation">Passwort bestätigen <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="cis-input w-full" required>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Konto erstellen</button>
                <a href="{{ route('user') }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
