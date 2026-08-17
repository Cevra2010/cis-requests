@extends('layout.app')

@section('content')
<div class="max-w-3xl space-y-5">

    {{-- ── Profilbild + Stammdaten ── --}}
    <div class="cis-card">
        <form action="{{ route('dashboard.self.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="flex items-start gap-6 mb-6">
                {{-- Avatar --}}
                <div class="shrink-0">
                    <div class="relative group w-24 h-24">
                        <img id="avatar-preview"
                             src="{{ $user->avatarUrl() }}"
                             alt="{{ $user->name() }}"
                             class="w-24 h-24 rounded-full object-cover ring-2 ring-gray-200">
                        <label for="avatar-input"
                               class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center
                                      opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            <i class="fa fa-camera text-white text-lg"></i>
                        </label>
                        <input type="file" id="avatar-input" name="avatar" accept="image/*" class="sr-only"
                               onchange="previewAvatar(this)">
                    </div>
                    <p class="text-xs text-gray-400 text-center mt-1.5">max. 2 MB</p>
                </div>

                {{-- Name --}}
                <div class="flex-1">
                    <p class="text-xl font-semibold text-gray-900">{{ $user->name() }}</p>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach($user->groups as $group)
                            <span class="cis-badge text-white text-xs"
                                  style="background: {{ $group->color ?? '#6B7280' }}">
                                {{ $group->name }}
                            </span>
                        @endforeach
                        @foreach($user->roles as $role)
                            <span class="cis-badge text-white text-xs"
                                  style="background: {{ $role->color ?? '#8B5CF6' }}">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                <div>
                    <label class="cis-label" for="email">E-Mail <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" class="cis-input w-full"
                           value="{{ old('email', $user->email) }}" required>
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="cis-label" for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone" class="cis-input w-full"
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="+49 123 456789">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="cis-label" for="birthdate">Geburtsdatum</label>
                    <input type="date" id="birthdate" name="birthdate" class="cis-input w-full"
                           value="{{ old('birthdate', $user->birthdate?->format('Y-m-d')) }}">
                    @error('birthdate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($user->birthdate)
                <div class="flex items-end">
                    <p class="text-sm text-gray-500 pb-2">
                        <i class="fa fa-cake-candles mr-1 text-primary-400"></i>
                        {{ $user->birthdate->age }} Jahre
                        · {{ $user->birthdate->format('d.m.Y') }}
                    </p>
                </div>
                @endif

                <div class="md:col-span-2">
                    <label class="cis-label" for="bio">Über mich</label>
                    <textarea id="bio" name="bio" class="cis-input w-full" rows="3"
                              placeholder="Kurze Beschreibung Ihrer Tätigkeit…"
                              maxlength="500">{{ old('bio', $user->bio) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400 text-right">
                        <span id="bio-count">{{ strlen($user->bio ?? '') }}</span>/500
                    </p>
                    @error('bio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Profil speichern</button>
                <p class="text-xs text-gray-400">
                    Mitglied seit {{ $user->created_at->format('d.m.Y') }}
                </p>
            </div>
        </form>
    </div>

    {{-- ── Passwort ändern ── --}}
    <div class="cis-card">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">
            <i class="fa fa-key mr-1.5 text-gray-400"></i> Passwort ändern
        </h3>

        <form action="{{ route('dashboard.self.password') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="cis-label" for="current_password">Aktuelles Passwort <span class="text-red-500">*</span></label>
                <input type="password" id="current_password" name="current_password" class="cis-input w-full max-w-sm" required>
                @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-lg">
                <div>
                    <label class="cis-label" for="new_password">Neues Passwort <span class="text-red-500">*</span></label>
                    <input type="password" id="new_password" name="password" class="cis-input w-full"
                           placeholder="Mindestens 8 Zeichen" required>
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="password_confirmation">Wiederholen <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="cis-input w-full" required>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary">Passwort ändern</button>
        </form>
    </div>

    {{-- ── Sitzungsinfo ── --}}
    <div class="cis-card bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">
            <i class="fa fa-circle-info mr-1.5 text-gray-400"></i> Kontoinformationen
        </h3>
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Konto erstellt</dt>
                <dd class="font-medium text-gray-700 mt-0.5">{{ $user->created_at->format('d.m.Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Letzte Änderung</dt>
                <dd class="font-medium text-gray-700 mt-0.5">{{ $user->updated_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Gruppen</dt>
                <dd class="font-medium text-gray-700 mt-0.5">{{ $user->groups->count() }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Rollen</dt>
                <dd class="font-medium text-gray-700 mt-0.5">{{ $user->roles->count() }}</dd>
            </div>
        </dl>
    </div>

</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('bio').addEventListener('input', function() {
    document.getElementById('bio-count').textContent = this.value.length;
});
</script>
@endsection
