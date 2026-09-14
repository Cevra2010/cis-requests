<div>
    @include('lager::livewire._qr-scanner')

    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-semibold text-gray-800">Ware buchen</h2>
            <p class="text-sm text-gray-500 mt-0.5">Erst Lagerort wählen, dann offene Wareneingangspositionen einbuchen.</p>
        </div>
        <a href="{{ route('lager.index') }}" class="btn btn-ghost btn-sm">
            <i class="fa fa-arrow-left mr-1.5"></i>Warenübersicht
        </a>
    </div>

    @if(! $wareneingangEnabled)
    <div class="cis-card text-center py-12 text-gray-400">
        <i class="fa fa-truck-ramp-box text-3xl mb-3 block"></i>
        <p class="text-sm font-medium">Das Wareneingang-Modul ist nicht aktiv.</p>
        <p class="text-xs text-gray-400 mt-1">Ohne Wareneingang gibt es hier nichts einzubuchen – Lagerorte, Warenübersicht und Verschieben funktionieren unabhängig davon weiter.</p>
    </div>
    @elseif(! $lagerort)
    <div class="cis-card">
        <div class="flex items-center justify-between mb-3">
            <p class="cis-label mb-0">Lagerort wählen</p>
            <button type="button" x-data
                    @click="$store.qrScanner.launch((text) => { window.location.href = '{{ route('lager.buchen', 'LAGERORT_ID') }}'.replace('LAGERORT_ID', $store.qrScanner.extractId(text)); })"
                    class="btn btn-ghost btn-sm shrink-0">
                <i class="fa fa-qrcode mr-1.5"></i>QR-Code scannen
            </button>
        </div>
        <div class="space-y-1">
            @forelse($lagerorte as $l)
                <button type="button" wire:click="selectLagerort('{{ $l->cis_row_id }}')"
                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm text-left hover:bg-gray-50 transition-colors">
                    <i class="fa fa-box-archive text-gray-300 text-xs"></i>
                    <span>{{ str_repeat('— ', $l->depth) }}{{ $l->name }}</span>
                </button>
            @empty
                <p class="text-sm text-gray-400 italic py-6 text-center">
                    Noch keine Lagerorte angelegt. <a href="{{ route('lager.lagerorte') }}" class="text-primary-600 hover:underline">Jetzt anlegen</a>.
                </p>
            @endforelse
        </div>
    </div>
    @else
    <div class="cis-card mb-4 flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-400">Ziel-Lagerort</p>
            <p class="text-sm font-semibold text-gray-800">{{ $lagerort->path() }}</p>
        </div>
        <button type="button" wire:click="$set('lagerortId', null)" class="btn btn-ghost btn-sm">
            <i class="fa fa-rotate mr-1.5"></i>Anderer Lagerort
        </button>
    </div>

    <div class="mb-3">
        <div class="relative">
            <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Produkt suchen…"
                   class="cis-input w-full text-sm pl-8">
        </div>
    </div>

    <div class="space-y-3">
        @forelse($openItems as $item)
        <div wire:key="open-item-{{ $item->cis_row_id }}" class="rounded-xl border-2 border-gray-200 bg-white p-4">
            <p class="text-sm font-semibold text-gray-800">{{ $item->position?->product?->name ?? '–' }}</p>
            <p class="text-xs text-gray-400 mt-0.5">
                {{ $item->receipt?->project?->name }}
                &middot; {{ $item->receipt?->offer?->source?->name ?? $item->receipt?->source?->name ?? 'Interne Quelle' }}
                &middot; noch <strong>{{ $item->remaining }}</strong> offen
            </p>

            <div class="mt-3 flex items-center gap-2">
                <input type="number" min="1" max="{{ $item->remaining }}" inputmode="numeric"
                       wire:model="qty.{{ $item->cis_row_id }}"
                       placeholder="{{ $item->remaining }}"
                       class="w-20 text-center cis-input py-2 text-sm tabular-nums">
                <button type="button" wire:click="book('{{ $item->cis_row_id }}')" class="btn btn-primary btn-sm">
                    <i class="fa fa-box-open mr-1.5"></i>Einbuchen
                </button>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 italic text-center py-12">
            <i class="fa fa-circle-check text-emerald-400 text-lg block mb-2"></i>
            Keine offenen Positionen zum Einlagern.
        </p>
        @endforelse
    </div>
    @endif
</div>
