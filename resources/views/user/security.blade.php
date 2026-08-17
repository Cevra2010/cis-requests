@extends('layout.app')

@section('header_actions')
    <a href="{{ route('user.edit', $user) }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-arrow-left mr-1"></i> Zurück
    </a>
@endsection

@section('content')
<div class="max-w-md">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-1">Passwort ändern</h2>
        <p class="text-sm text-gray-500 mb-4">{{ $user->name() }}</p>

        <form action="{{ route('user.security.update', $user) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="cis-label" for="password">Neues Passwort <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" class="cis-input w-full"
                       placeholder="Mindestens 8 Zeichen" required>
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="password_confirmation">Passwort bestätigen <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="cis-input w-full" required>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Passwort ändern</button>
                <a href="{{ route('user.edit', $user) }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
