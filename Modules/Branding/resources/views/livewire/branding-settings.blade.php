<div class="space-y-8">

    {{-- Erfolgsmeldung --}}
    @if($saved)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
        <i class="fa fa-circle-check"></i>
        Einstellungen gespeichert.
    </div>
    @endif

    {{-- ── Logo ───────────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa fa-image text-gray-400 w-4"></i> Logo
        </h3>

        <div class="flex items-start gap-6">

            {{-- Vorschau --}}
            <div class="shrink-0 w-28 h-20 border border-gray-200 rounded-xl flex items-center justify-center bg-gray-50 overflow-hidden relative">
                @if($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="Logo Vorschau" class="max-w-full max-h-full object-contain p-2">
                @elseif($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="max-w-full max-h-full object-contain p-2">
                @else
                    <i class="fa fa-image text-2xl text-gray-300"></i>
                @endif

                {{-- Upload-Spinner --}}
                <div wire:loading wire:target="logo"
                     class="absolute inset-0 bg-white/80 flex items-center justify-center">
                    <i class="fa fa-spinner fa-spin text-gray-400"></i>
                </div>
            </div>

            <div class="flex-1 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Logo hochladen
                        <span class="text-gray-400 font-normal">(PNG, JPG, SVG · max. 2 MB)</span>
                    </label>
                    <input type="file"
                           wire:model="logo"
                           accept="image/png,image/jpeg,image/svg+xml"
                           class="block w-full text-sm text-gray-600
                                  file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                  file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700
                                  hover:file:bg-gray-200 cursor-pointer">
                    @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    @if($logo)
                    <p class="mt-1 text-xs text-indigo-600 flex items-center gap-1">
                        <i class="fa fa-circle-check"></i>
                        Bereit zum Speichern — noch nicht gespeichert.
                    </p>
                    @endif
                </div>

                @if($logoUrl && !$logo)
                <button type="button"
                        wire:click="removeLogo"
                        class="text-xs text-red-500 hover:text-red-700 transition-colors flex items-center gap-1">
                    <i class="fa fa-trash-can"></i> Logo entfernen
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Kopfzeile ───────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa fa-rectangle-ad text-gray-400 w-4"></i> Kopfzeile
        </h3>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Organisation / Behörde</label>
                <input type="text"
                       wire:model.defer="header_line1"
                       placeholder="z.B. Landkreis Musterstadt"
                       class="w-full text-sm border border-gray-200 rounded-xl px-3.5 py-2.5
                              focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none">
                @error('header_line1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Unterzeile <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text"
                       wire:model.defer="header_line2"
                       placeholder="z.B. Feuerwehr und Katastrophenschutz"
                       class="w-full text-sm border border-gray-200 rounded-xl px-3.5 py-2.5
                              focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none">
                @error('header_line2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- ── Fußzeile ────────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa fa-grip-lines text-gray-400 w-4"></i> Fußzeile
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Links</label>
                <input type="text"
                       wire:model.defer="footer_left"
                       placeholder="z.B. Ausschreibung — vertraulich"
                       class="w-full text-sm border border-gray-200 rounded-xl px-3.5 py-2.5
                              focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none">
                @error('footer_left') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Rechts</label>
                <input type="text"
                       wire:model.defer="footer_right"
                       placeholder="z.B. Erstellt mit CIS Requests"
                       class="w-full text-sm border border-gray-200 rounded-xl px-3.5 py-2.5
                              focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none">
                @error('footer_right') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- ── Akzentfarbe ─────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa fa-palette text-gray-400 w-4"></i> Akzentfarbe
        </h3>
        <p class="text-xs text-gray-500 mb-4">Wird für Trennlinien im Dokument-Header und -Footer verwendet.</p>
        <div class="flex items-center gap-3">
            <input type="color"
                   wire:model.defer="accent_color"
                   class="w-12 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
            <input type="text"
                   wire:model.defer="accent_color"
                   maxlength="7"
                   placeholder="#4F46E5"
                   class="w-32 text-sm font-mono border border-gray-200 rounded-xl px-3.5 py-2.5
                          focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none">
            @error('accent_color') <p class="ml-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- ── Vorschau ─────────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa fa-eye text-gray-400 w-4"></i> Vorschau
        </h3>
        <div class="bg-slate-100 rounded-xl p-6">
            <div class="max-w-xl mx-auto bg-white shadow px-10 py-6 rounded">
                @include('branding::partials.document-header', ['preview' => true])
                <div class="py-8 text-xs text-gray-300 italic text-center border-t border-b border-gray-100 my-4">
                    — Dokumentinhalt —
                </div>
                @include('branding::partials.document-footer', ['preview' => true])
            </div>
        </div>
    </div>

    {{-- Speichern --}}
    <div class="flex justify-end">
        <button type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60
                       text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors shadow-sm">
            <i class="fa fa-floppy-disk"></i>
            <span wire:loading.remove wire:target="save">Speichern</span>
            <span wire:loading wire:target="save">Speichern…</span>
        </button>
    </div>

</div>
