<div class="max-w-2xl">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-800 mb-1">Firmenlizenz</h2>
        <p class="text-sm text-gray-500 mb-6">
            Legt die Master-ID dieser Installation fest. Modul-Lizenzen (z. B. Branding, Wareneingang)
            sind an diese Master-ID gebunden und funktionieren nur, solange die passende Firmenlizenz aktiv ist.
        </p>

        @if(session('success'))
            <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ session('success') }}</div>
        @endif

        @if($hasMasterLicense && ! $editing)
            <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 mb-4">
                <p class="text-sm text-emerald-700 flex items-center gap-1.5">
                    <i class="fa fa-building-shield"></i>
                    Aktiv für <strong>{{ $licensee }}</strong>
                </p>
                <p class="text-xs text-emerald-600 mt-1.5">
                    Master-ID: <span class="font-mono">{{ $masterId }}</span>
                </p>
                <p class="text-xs text-emerald-600 mt-0.5">
                    @if($expiresAt)
                        Gültig bis {{ \Carbon\Carbon::parse($expiresAt)->format('d.m.Y') }}
                    @else
                        Unbegrenzt gültig
                    @endif
                </p>
            </div>
            <button type="button" wire:click="edit" class="btn btn-ghost btn-sm">
                <i class="fa fa-rotate-right mr-1.5"></i> Lizenz ersetzen
            </button>
        @elseif(! $hasMasterLicense && ! $editing)
            <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 mb-4 text-sm text-amber-700">
                <i class="fa fa-triangle-exclamation mr-1.5"></i>
                Keine Firmenlizenz aktiv. Modul-Lizenzen können erst danach aktiviert werden.
            </div>
            <button type="button" wire:click="edit" class="btn btn-primary btn-sm">
                <i class="fa fa-key mr-1.5"></i> Lizenzschlüssel eingeben
            </button>
        @else
            <div class="space-y-1.5">
                <label class="cis-label" for="licenseKey">Lizenzschlüssel</label>
                <textarea id="licenseKey" wire:model="licenseKey" rows="4"
                          class="cis-input w-full font-mono text-xs @error('licenseKey') is-invalid @enderror"
                          placeholder="eyJ0eXBlIjoibWFzdGVyIiwi..."></textarea>
                @error('licenseKey')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2 pt-3">
                <button type="button" wire:click="cancel" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="activate" class="btn btn-primary btn-sm">Aktivieren</button>
            </div>
        @endif
    </div>
</div>
