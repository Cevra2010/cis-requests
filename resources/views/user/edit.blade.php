@extends('layout.app')

@section('header_actions')
    <a href="{{ route('user.edit.membership', $user) }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-users mr-1"></i> Gruppen & Rollen
    </a>
    <a href="{{ route('user.permissions', $user) }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-shield-halved mr-1"></i> Berechtigungen
    </a>
    <a href="{{ route('user.security', $user) }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-key mr-1"></i> Passwort
    </a>
    <a href="{{ route('user.delete', $user) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Konto bearbeiten</h2>

        <form action="{{ route('user.update', $user) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="cis-label" for="firstname">Vorname <span class="text-red-500">*</span></label>
                    <input type="text" id="firstname" name="firstname" class="cis-input w-full"
                           value="{{ old('firstname', $user->firstname) }}" required>
                    @error('firstname')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="lastname">Nachname <span class="text-red-500">*</span></label>
                    <input type="text" id="lastname" name="lastname" class="cis-input w-full"
                           value="{{ old('lastname', $user->lastname) }}" required>
                    @error('lastname')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="cis-label" for="email">E-Mail <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" class="cis-input w-full"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('user') }}" class="btn btn-ghost">Zurück</a>
            </div>
        </form>
    </div>

    <div class="cis-card mt-4">
        <p class="text-xs text-gray-400">
            Erstellt {{ $user->created_at->format('d.m.Y') }} · Geändert {{ $user->updated_at->format('d.m.Y') }}
        </p>
    </div>
</div>
@endsection
