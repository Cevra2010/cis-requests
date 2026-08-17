@extends('layout.auth')

@section('title', 'Anmelden')

@section('content')
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Willkommen zurück</h2>
    <p class="text-sm text-gray-500 mb-6">Bitte melden Sie sich an, um fortzufahren.</p>

    @if($errors->any())
        <div class="cis-error mb-4">
            <i class="fa fa-exclamation-circle"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form action="{{ route('auth.login.submit') }}" method="POST" class="space-y-4">
        @csrf
        <div class="cis-form-group">
            <label for="email" class="cis-label">E-Mail Adresse</label>
            <input type="email" name="email" id="email" autocomplete="email"
                   value="{{ old('email') }}"
                   class="cis-input @error('email') is-invalid @enderror"
                   placeholder="name@firma.de">
        </div>
        <div class="cis-form-group">
            <label for="password" class="cis-label">Passwort</label>
            <input type="password" name="password" id="password" autocomplete="current-password"
                   class="cis-input @error('password') is-invalid @enderror"
                   placeholder="••••••••">
        </div>
        <button type="submit" class="btn-primary w-full justify-center py-2.5">
            Anmelden
        </button>
    </form>
@endsection
