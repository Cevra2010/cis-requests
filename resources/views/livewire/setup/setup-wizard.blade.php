<div>
    @if($completed)
        {{-- ── Fertig ── --}}
        <div class="text-center py-4">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 mb-4">
                <i class="fa fa-check text-2xl text-emerald-600"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Einrichtung abgeschlossen</h2>
            <p class="text-sm text-gray-500 mb-6">
                Willkommen, {{ $firstname }}. Dein Administrator-Konto ist bereit.
            </p>

            @if(!empty($demoSummary))
            <div class="text-left mb-6 px-4 py-3 rounded-xl bg-gray-50 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Demo-Daten eingespielt</p>
                <ul class="text-xs text-gray-500 grid grid-cols-2 gap-x-4 gap-y-0.5">
                    @foreach($demoSummary as $label => $count)
                        <li>{{ $label }}: <strong class="text-gray-700 tabular-nums">{{ $count }}</strong></li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Normaler Link statt Livewire-Aktion: session()->regenerate() in finish()
                 hat das clientseitig zwischengespeicherte CSRF-Token invalidiert, ein
                 weiterer Livewire-Request auf dieser Seite würde mit 419 fehlschlagen. --}}
            <a href="{{ route('dashboard') }}" class="btn-primary w-full justify-center py-2.5">
                Zum Dashboard
            </a>
        </div>
    @else
        {{-- ── Schrittanzeige ── --}}
        <div class="flex items-center justify-center gap-2 mb-8">
            @foreach(['Lizenz', 'Konto', 'Einstellungen'] as $i => $label)
            @php $n = $i + 1; @endphp
            <div class="flex items-center gap-2">
                <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold shrink-0
                    {{ $step > $n ? 'bg-emerald-500 text-white' : ($step === $n ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-400') }}">
                    @if($step > $n)
                        <i class="fa fa-check text-[10px]"></i>
                    @else
                        {{ $n }}
                    @endif
                </div>
                <span class="text-xs font-medium hidden sm:inline {{ $step === $n ? 'text-gray-800' : 'text-gray-400' }}">{{ $label }}</span>
            </div>
            @if($n < 3)
                <div class="w-8 h-px {{ $step > $n ? 'bg-emerald-400' : 'bg-gray-200' }}"></div>
            @endif
            @endforeach
        </div>

        {{-- ── Schritt 1: Firmenlizenz ── --}}
        @if($step === 1)
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Firmenlizenz</h2>
        <p class="text-sm text-gray-500 mb-6">Bitte gib den Lizenzschlüssel deiner Firma ein, um fortzufahren.</p>

        <div class="space-y-1.5">
            <label for="licenseKey" class="cis-label">Lizenzschlüssel</label>
            <textarea id="licenseKey" wire:model="licenseKey" rows="4"
                      class="cis-input w-full font-mono text-xs @error('licenseKey') is-invalid @enderror"
                      placeholder="eyJ0eXBlIjoibWFzdGVyIiwi..."></textarea>
            @error('licenseKey')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="button" wire:click="activateLicense" class="btn-primary w-full justify-center py-2.5 mt-6">
            Lizenz aktivieren
        </button>
        @endif

        {{-- ── Schritt 2: Administrator-Konto ── --}}
        @if($step === 2)
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Administrator-Konto</h2>
        <p class="text-sm text-gray-500 mb-6">
            @if($licenseeName)Lizenziert für <strong>{{ $licenseeName }}</strong>. @endif
            Lege das erste Benutzerkonto an — es erhält alle Rechte.
        </p>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="firstname" class="cis-label">Vorname</label>
                    <input type="text" id="firstname" wire:model="firstname"
                           class="cis-input w-full @error('firstname') is-invalid @enderror">
                    @error('firstname')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lastname" class="cis-label">Nachname</label>
                    <input type="text" id="lastname" wire:model="lastname"
                           class="cis-input w-full @error('lastname') is-invalid @enderror">
                    @error('lastname')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="email" class="cis-label">E-Mail Adresse</label>
                <input type="email" id="email" wire:model="email" autocomplete="email"
                       class="cis-input w-full @error('email') is-invalid @enderror" placeholder="name@firma.de">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="password" class="cis-label">Passwort</label>
                    <input type="password" id="password" wire:model="password" autocomplete="new-password"
                           class="cis-input w-full @error('password') is-invalid @enderror" placeholder="mind. 8 Zeichen">
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="cis-label">Passwort bestätigen</label>
                    <input type="password" id="password_confirmation" wire:model="password_confirmation" autocomplete="new-password"
                           class="cis-input w-full">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-6">
            <button type="button" wire:click="back" class="btn-ghost py-2.5 px-4">Zurück</button>
            <button type="button" wire:click="nextFromAccount" class="btn-primary flex-1 justify-center py-2.5">Weiter</button>
        </div>
        @endif

        {{-- ── Schritt 3: Grundeinstellungen & Abschluss ── --}}
        @if($step === 3)
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Grundeinstellungen</h2>
        <p class="text-sm text-gray-500 mb-6">Fast fertig — lege noch ein paar Standardwerte fest.</p>

        <div class="space-y-5">
            <div>
                <label for="defaultMinOrderValue" class="cis-label">Mindestbestellwert (Standard)</label>
                <div class="relative">
                    <input type="text" id="defaultMinOrderValue" wire:model="defaultMinOrderValue"
                           class="cis-input w-full pr-8 @error('defaultMinOrderValue') is-invalid @enderror">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">€</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">Globaler Standard je Anbieter, kann später je Projekt überschrieben werden.</p>
                @error('defaultMinOrderValue')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-start gap-3 px-4 py-3 rounded-xl border border-gray-200 cursor-pointer hover:border-primary-300 transition-colors">
                <input type="checkbox" wire:model="wantsDemoData" class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span>
                    <span class="block text-sm font-medium text-gray-800">Demo-Daten einspielen</span>
                    <span class="block text-xs text-gray-500 mt-0.5">5 Benutzer, 2 Gruppen, Kategorien, Produktquellen, Produkte mit Preisen und 2 Demo-Projekte zum Ausprobieren.</span>
                </span>
            </label>
        </div>

        <div class="flex items-center gap-2 mt-6">
            <button type="button" wire:click="back" class="btn-ghost py-2.5 px-4">Zurück</button>
            <button type="button" wire:click="finish" class="btn-primary flex-1 justify-center py-2.5">
                Einrichtung abschließen
            </button>
        </div>
        @endif
    @endif
</div>
