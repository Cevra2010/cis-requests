<div class="space-y-8">

    {{-- ── Core-Module ──────────────────────────────────────────────────────── --}}
    <div>
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-sm font-semibold text-gray-700">Core-Module</h2>
            <span class="text-xs text-gray-400">Systemmodule · immer aktiv · nicht deinstallierbar</span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($coreModules as $name => $info)
            <div class="cis-card flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center shrink-0">
                    <i class="fa {{ $info['icon'] }} text-primary-600 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $name }}</p>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-100 text-primary-700">Core</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">{{ $info['description'] }}</p>
                </div>
                <span class="text-[11px] text-emerald-600 font-medium flex items-center gap-1 shrink-0">
                    <i class="fa fa-circle text-[8px]"></i> Aktiv
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Installierte Module ───────────────────────────────────────────────── --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-semibold text-gray-700">Installierte Module</h2>
                <span class="text-xs text-gray-400">{{ $installedModules->count() }} Modul(e)</span>
            </div>
            <button type="button" wire:click="$set('showInstallModal', true)" class="btn btn-primary btn-sm">
                <i class="fa fa-plus mr-1.5"></i> Modul installieren
            </button>
        </div>

        @if($installedModules->count())
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($installedModules as $module)
            @php
                $hasLicense     = $module['license'] !== null;
                $licenseExpired = $hasLicense && $module['license']->isExpired();
                $licenseValid   = $hasLicense && !$licenseExpired;
                $needsLicense   = $module['requires_license'] && !$licenseValid;
            @endphp

            <div class="cis-card flex flex-col gap-4 {{ ($module['enabled'] && !$needsLicense) ? '' : 'opacity-70' }}">
                <div class="flex items-start gap-3">
                    {{-- Icon --}}
                    <div class="w-10 h-10 rounded-xl {{ $module['enabled'] ? 'bg-gray-100' : 'bg-gray-50' }} flex items-center justify-center shrink-0">
                        <i class="fa fa-puzzle-piece text-gray-400 text-sm"></i>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                            <p class="text-sm font-semibold text-gray-800">{{ $module['name'] }}</p>
                            @if($module['core'])
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-100 text-primary-700">Core</span>
                            @endif
                            @if($module['requires_license'])
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold
                                    {{ $licenseValid    ? 'bg-emerald-100 text-emerald-700'
                                     : ($hasLicense     ? 'bg-red-100 text-red-700'
                                     :                    'bg-amber-100 text-amber-700') }}">
                                    <i class="fa fa-key mr-0.5 text-[9px]"></i>
                                    {{ $licenseValid ? 'Lizenziert' : ($hasLicense ? 'Abgelaufen' : 'Lizenz erforderlich') }}
                                </span>
                            @endif
                            <span class="text-[10px] text-gray-400 ml-auto">v{{ $module['version'] }}</span>
                        </div>
                        @if($module['description'])
                            <p class="text-xs text-gray-500 leading-relaxed">{{ $module['description'] }}</p>
                        @else
                            <p class="text-xs text-gray-300 italic">Keine Beschreibung</p>
                        @endif
                    </div>

                    {{-- Toggle / Status --}}
                    <div class="shrink-0">
                        @if(!$module['core'])
                        <button type="button"
                                wire:click="toggle('{{ $module['name'] }}')"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                                       {{ $module['enabled'] ? 'bg-emerald-500' : 'bg-gray-200' }}"
                                title="{{ $module['enabled'] ? 'Deaktivieren' : ($needsLicense ? 'Lizenz eingeben' : 'Aktivieren') }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                         {{ $module['enabled'] ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                        </button>
                        @else
                        <span class="text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                            <i class="fa fa-circle text-[8px]"></i> Aktiv
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Lizenz-Details (wenn lizenziert) --}}
                @if($module['requires_license'] && $hasLicense)
                <div class="border-t border-gray-100 pt-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] text-gray-500 truncate">
                            <i class="fa fa-user text-[9px] mr-1 text-gray-400"></i>
                            {{ $module['license']->licensee }}
                        </p>
                        @if($module['license']->expires_at)
                        <p class="text-[11px] {{ $licenseExpired ? 'text-red-500' : 'text-gray-400' }}">
                            <i class="fa fa-calendar text-[9px] mr-1"></i>
                            {{ $licenseExpired ? 'Abgelaufen' : 'Gültig' }} bis
                            {{ $module['license']->expires_at->format('d.m.Y') }}
                        </p>
                        @else
                        <p class="text-[11px] text-gray-400"><i class="fa fa-infinity text-[9px] mr-1"></i>Unbegrenzt gültig</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                wire:click="openLicenseModal('{{ $module['name'] }}')"
                                class="text-[11px] text-gray-400 hover:text-primary-600 transition-colors"
                                title="Lizenz ersetzen">
                            <i class="fa fa-rotate-right"></i>
                        </button>
                        <button type="button"
                                wire:click="removeLicense('{{ $module['name'] }}')"
                                wire:confirm="Lizenz für '{{ $module['name'] }}' entfernen? Das Modul wird deaktiviert."
                                class="text-[11px] text-gray-400 hover:text-red-500 transition-colors"
                                title="Lizenz entfernen">
                            <i class="fa fa-trash-can"></i>
                        </button>
                    </div>
                </div>
                @elseif($module['requires_license'] && !$hasLicense)
                <div class="border-t border-gray-100 pt-3">
                    <button type="button"
                            wire:click="openLicenseModal('{{ $module['name'] }}')"
                            class="w-full flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg text-xs
                                   font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors">
                        <i class="fa fa-key text-[10px]"></i> Lizenzschlüssel eingeben
                    </button>
                </div>
                @endif

                {{-- Footer: Pfad + Löschen --}}
                @if(!$module['core'])
                <div class="flex items-center gap-2 border-t border-gray-100 pt-3">
                    <p class="text-[10px] text-gray-300 truncate flex-1">{{ basename($module['path']) }}</p>
                    <button type="button"
                            wire:click="delete('{{ $module['name'] }}')"
                            wire:confirm="Modul '{{ $module['name'] }}' wirklich deinstallieren? Alle Moduldaten werden gelöscht."
                            class="text-gray-300 hover:text-red-500 transition-colors"
                            title="Deinstallieren">
                        <i class="fa fa-trash-can text-xs"></i>
                    </button>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="cis-card text-center py-10 text-gray-400">
            <i class="fa fa-puzzle-piece text-2xl mb-2 block"></i>
            <p class="text-sm">Keine Module installiert.</p>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         License Modal
    ══════════════════════════════════════════════════════════════════════ --}}
    @if($showLicenseModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         @keydown.escape.window="$wire.set('showLicenseModal', false)">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
             wire:click="$set('showLicenseModal', false)"></div>

        <div class="relative z-10 w-full max-w-lg mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Lizenz aktivieren</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Modul: <span class="font-medium text-gray-600">{{ $licenseModule }}</span></p>
                </div>
                <button wire:click="$set('showLicenseModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i class="fa fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="px-6 py-5 space-y-4">

                {{-- Key Input --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Lizenzschlüssel</label>
                    <textarea wire:model="licenseKey"
                              rows="3"
                              placeholder="eyJ..."
                              class="cis-input w-full font-mono text-xs resize-none"
                              spellcheck="false"></textarea>
                    @if($licenseError)
                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1.5">
                            <i class="fa fa-circle-exclamation"></i> {{ $licenseError }}
                        </p>
                    @endif
                </div>

                {{-- Preview Result --}}
                @if($licensePreview)
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 space-y-1.5">
                    <p class="text-xs font-semibold text-emerald-700 flex items-center gap-1.5">
                        <i class="fa fa-circle-check"></i> Gültiger Lizenzschlüssel
                    </p>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-emerald-800">
                        <div>
                            <span class="text-emerald-500">Lizenznehmer:</span>
                            <span class="font-medium ml-1">{{ $licensePreview['licensee'] }}</span>
                        </div>
                        <div>
                            <span class="text-emerald-500">Ausgestellt:</span>
                            <span class="font-medium ml-1">{{ $licensePreview['issued'] ?? '—' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-emerald-500">Gültig bis:</span>
                            <span class="font-medium ml-1">
                                {{ $licensePreview['expires'] ? \Carbon\Carbon::parse($licensePreview['expires'])->format('d.m.Y') : 'Unbegrenzt' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Info Box --}}
                <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs text-blue-700">
                    <i class="fa fa-circle-info mr-1.5"></i>
                    Lizenzschlüssel sind modulspezifisch und kryptografisch signiert.
                    Sie können nicht für andere Module verwendet werden.
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" wire:click="$set('showLicenseModal', false)" class="btn btn-ghost btn-sm">
                        Abbrechen
                    </button>
                    @if(!$licensePreview)
                    <button type="button" wire:click="previewLicense" class="btn btn-ghost btn-sm">
                        <i class="fa fa-magnifying-glass mr-1.5"></i> Prüfen
                    </button>
                    @endif
                    <button type="button"
                            wire:click="{{ $licensePreview ? 'activateLicense' : 'previewLicense' }}"
                            class="btn btn-primary btn-sm">
                        <span wire:loading.remove>
                            @if($licensePreview)
                                <i class="fa fa-check mr-1.5"></i> Aktivieren
                            @else
                                <i class="fa fa-magnifying-glass mr-1.5"></i> Prüfen & Aktivieren
                            @endif
                        </span>
                        <span wire:loading><i class="fa fa-spinner fa-spin mr-1.5"></i> Prüfe…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         Install Modal
    ══════════════════════════════════════════════════════════════════════ --}}
    @if($showInstallModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         @keydown.escape.window="$wire.set('showInstallModal', false)">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
             wire:click="$set('showInstallModal', false)"></div>

        <div class="relative z-10 w-full max-w-lg mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Modul installieren</h3>
                <button wire:click="$set('showInstallModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i class="fa fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="flex border-b border-gray-100">
                <button type="button" wire:click="$set('installTab', 'upload')"
                        class="flex-1 px-4 py-3 text-sm font-medium transition-colors
                               {{ $installTab === 'upload' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-700' }}">
                    <i class="fa fa-file-zipper mr-1.5"></i> ZIP-Upload
                </button>
                <button type="button" wire:click="$set('installTab', 'composer')"
                        class="flex-1 px-4 py-3 text-sm font-medium transition-colors
                               {{ $installTab === 'composer' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-700' }}">
                    <i class="fa fa-terminal mr-1.5"></i> Composer
                </button>
            </div>

            <div class="px-6 py-5">
                @if($installTab === 'upload')
                <form wire:submit="installZip" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Modul-Paket (ZIP)</label>
                        <input type="file" wire:model="zipFile" accept=".zip"
                               class="block w-full text-sm text-gray-600 border border-gray-200 rounded-lg
                                      file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                      file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700
                                      hover:file:bg-primary-100 cursor-pointer">
                        @error('zipFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs text-blue-700">
                        <i class="fa fa-circle-info mr-1.5"></i>
                        Das ZIP muss eine <code class="font-mono bg-blue-100 px-1 rounded">module.json</code> enthalten.
                    </div>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" wire:click="$set('showInstallModal', false)" class="btn btn-ghost btn-sm">Abbrechen</button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="installZip"><i class="fa fa-upload mr-1.5"></i> Installieren</span>
                            <span wire:loading wire:target="installZip"><i class="fa fa-spinner fa-spin mr-1.5"></i> Lädt…</span>
                        </button>
                    </div>
                </form>
                @else
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Composer-Paketname</label>
                        <input type="text" wire:model="composerPackage" placeholder="vendor/package-name"
                               class="cis-input w-full font-mono text-sm">
                        @error('composerPackage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-xs text-amber-700">
                        <i class="fa fa-triangle-exclamation mr-1.5"></i>
                        <strong>Hinweis:</strong> Erfordert Shell-Zugriff und Schreibrechte auf das Projektverzeichnis.
                    </div>
                    @if($composerOutput)
                    <div class="bg-gray-900 rounded-xl p-4 max-h-48 overflow-y-auto">
                        <pre class="text-xs text-gray-300 whitespace-pre-wrap font-mono">{{ $composerOutput }}</pre>
                    </div>
                    @endif
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" wire:click="$set('showInstallModal', false)" class="btn btn-ghost btn-sm">Abbrechen</button>
                        <button type="button" wire:click="installComposer" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                            <span wire:loading.remove wire:target="installComposer"><i class="fa fa-terminal mr-1.5"></i> Installieren</span>
                            <span wire:loading wire:target="installComposer"><i class="fa fa-spinner fa-spin mr-1.5"></i> Lädt…</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
