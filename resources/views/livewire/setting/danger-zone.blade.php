<div class="max-w-2xl mt-8">

    @if($lastMessage)
    <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-sm">
        <p class="font-medium text-emerald-700 flex items-center gap-1.5">
            <i class="fa fa-circle-check"></i> {{ $lastMessage }}
        </p>
        @if($lastSummary)
        <ul class="mt-2 text-xs text-emerald-600 grid grid-cols-2 gap-x-4 gap-y-0.5">
            @foreach($lastSummary as $label => $count)
            <li>{{ $label }}: <strong class="tabular-nums">{{ $count }}</strong></li>
            @endforeach
        </ul>
        @endif
    </div>
    @endif

    {{-- ── Gefahrenzone ── --}}
    <div class="cis-card border border-red-200">
        <div class="flex items-center gap-2 mb-1">
            <i class="fa fa-triangle-exclamation text-red-500"></i>
            <h2 class="text-base font-semibold text-red-700">Gefahrenzone</h2>
        </div>
        <p class="text-sm text-gray-500 mb-5">
            Diese Aktionen löschen Daten unwiderruflich. Installierte Module (z. B. Branding, Wareneingang) und
            deren Daten/Lizenzen sind davon nicht betroffen.
        </p>

        <div class="space-y-2">
            @foreach($actions as $key => $meta)
            <div class="flex items-center justify-between gap-4 px-4 py-3 rounded-xl border border-red-100 bg-red-50/40">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $meta['label'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $meta['description'] }}</p>
                </div>
                <button type="button" wire:click="open('{{ $key }}')"
                        class="btn btn-sm border border-red-300 text-red-600 hover:bg-red-100 shrink-0">
                    Ausführen
                </button>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Demo-Daten ── --}}
    <div class="cis-card mt-4">
        <h2 class="text-base font-semibold text-gray-800 mb-1">Demo-Daten</h2>
        <p class="text-sm text-gray-500 mb-4">
            Spielt Beispieldaten ein: 5 Benutzer, 2 Gruppen, ein paar Kategorien, 3 Produktquellen, 10 Produkte
            mit Preisen und 2 Demo-Projekte. Rein additiv — bestehende Daten werden nicht verändert.
        </p>
        <button type="button" wire:click="runDemoData" wire:confirm="Demo-Daten jetzt einspielen?"
                class="btn btn-primary btn-sm">
            <i class="fa fa-wand-magic-sparkles mr-1.5"></i> Demo-Daten einspielen
        </button>
    </div>

    {{-- ── Bestätigungs-Modal ── --}}
    @if($activeAction)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6" wire:key="modal-{{ $activeAction }}">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa fa-triangle-exclamation text-red-500"></i>
                <h3 class="text-base font-semibold text-gray-900">{{ $actions[$activeAction]['label'] }}</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">{{ $actions[$activeAction]['description'] }}</p>

            <label class="cis-label text-xs">
                Gib zur Bestätigung
                <span class="font-mono font-semibold text-red-600">{{ $actions[$activeAction]['phrase'] }}</span>
                ein
            </label>
            <input type="text" wire:model="confirmText" autofocus autocomplete="off"
                   class="cis-input w-full mt-1 @error('confirmText') is-invalid @enderror"
                   placeholder="{{ $actions[$activeAction]['phrase'] }}">
            @error('confirmText')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

            <div class="flex items-center justify-end gap-2 mt-5">
                <button type="button" wire:click="cancel" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="confirmReset"
                        class="btn btn-sm bg-red-600 text-white hover:bg-red-700 border border-red-600">
                    Endgültig ausführen
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
