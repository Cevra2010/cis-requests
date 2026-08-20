<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-semibold text-gray-800">Angebote vergleichen</h2>
            <p class="text-xs text-gray-500 mt-0.5">Preise je Position und Anbieter eintragen. Günstigster valider Preis je Zeile ist hervorgehoben.</p>
        </div>
        <button type="button" wire:click="openCreateModal" class="btn btn-primary btn-sm">
            <i class="fa fa-plus mr-1.5"></i> Angebot anlegen
        </button>
    </div>

    @if($positions->isEmpty())
        <div class="cis-card text-center py-10 text-gray-400">
            <i class="fa fa-boxes-stacked text-2xl mb-2"></i>
            <p class="text-sm">Diesem Projekt sind noch keine Produkte zugeordnet.</p>
        </div>
    @elseif($offers->isEmpty())
        <div class="cis-card text-center py-10 text-gray-400">
            <i class="fa fa-file-invoice-dollar text-2xl mb-2"></i>
            <p class="text-sm">Noch keine Angebote angelegt.</p>
        </div>
    @else
    {{-- ── Ansicht umschalten ── --}}
    <div class="flex items-center gap-1.5 mb-4 border border-gray-200 rounded-lg p-1 bg-gray-50 w-fit">
        <button type="button" wire:click="setViewMode('overview')"
                class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ $viewMode === 'overview' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' }}">
            <i class="fa fa-table-cells mr-1.5"></i>Gesamtübersicht
        </button>
        <button type="button" wire:click="setViewMode('sequential')"
                class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ $viewMode === 'sequential' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' }}">
            <i class="fa fa-list-ol mr-1.5"></i>Einzeln bearbeiten
        </button>
    </div>

    @if($viewMode === 'sequential')
        {{-- ── Angebote nacheinander bearbeiten ── --}}
        <div class="flex items-center gap-1.5 mb-4 flex-wrap">
            @foreach($offers as $offer)
            <button type="button" wire:click="selectOffer('{{ $offer->cis_row_id }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                        {{ $currentOffer && $currentOffer->cis_row_id === $offer->cis_row_id
                            ? 'border-primary-500 bg-primary-50 text-primary-700'
                            : 'border-gray-200 text-gray-500 hover:border-gray-300' }}
                        {{ !$offer->active ? 'opacity-40' : '' }}">
                {{ $offer->source->name }}
                @if(!$offer->active)<i class="fa fa-ban ml-1"></i>@endif
            </button>
            @endforeach
        </div>

        @if($currentOffer)
        <div class="cis-card">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ $currentOffer->source->name }}
                        <span class="text-xs font-normal text-gray-400 ml-1">Angebot {{ $currentOfferIndex + 1 }} von {{ $offers->count() }}</span>
                    </p>
                    @if($currentOffer->reference)
                        <p class="text-xs text-gray-400">{{ $currentOffer->reference }}</p>
                    @endif
                    @if(!$currentOffer->active)
                        <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700">
                            <i class="fa fa-ban"></i> Ausgeschlossen
                        </span>
                    @endif
                </div>
                <button type="button" wire:click="toggleActive('{{ $currentOffer->cis_row_id }}')"
                        class="btn btn-ghost btn-sm">
                    <i class="fa {{ $currentOffer->active ? 'fa-ban' : 'fa-rotate-left' }} mr-1.5"></i>
                    {{ $currentOffer->active ? 'Anbieter ausschließen' : 'Anbieter reaktivieren' }}
                </button>
            </div>

            <div class="divide-y divide-gray-50 border-t border-gray-100">
                @foreach($positions as $position)
                @php
                    $item = $matrix[$position->cis_row_id][$currentOffer->cis_row_id] ?? null;
                    $isCheapest = $item && !$item->not_offered && $item->price !== null
                        && $currentOffer->active
                        && (float) $item->price === (float) ($cheapestPerPosition[$position->cis_row_id] ?? null);
                @endphp
                <div class="flex items-center gap-4 py-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $position->product->name ?? '–' }}</p>
                        @if($position->note)<p class="text-xs text-gray-400">{{ $position->note }}</p>@endif
                        @if($deviation = $deviations[$position->cis_row_id] ?? null)
                        <p class="text-[10px] text-amber-600 mt-0.5 flex items-center gap-1">
                            <i class="fa fa-circle-info"></i>
                            Abweichend bestellt bei {{ $deviation['source_name'] }} ({{ number_format($deviation['awarded_price'], 2, ',', '.') }} € statt {{ number_format($deviation['cheapest_price'], 2, ',', '.') }} €)
                        </p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 w-14 text-center shrink-0">{{ $position->product_count }} Stk.</span>
                    @if($item)
                    <div class="flex items-center gap-1.5 shrink-0">
                        <input type="text"
                               value="{{ $item->not_offered ? '' : $item->price }}"
                               {{ $item->not_offered ? 'disabled' : '' }}
                               placeholder="Preis"
                               wire:change="saveItemPrice('{{ $currentOffer->cis_row_id }}', '{{ $position->cis_row_id }}', $event.target.value)"
                               class="cis-input py-1.5 px-2 text-sm w-28 {{ $isCheapest ? 'font-semibold text-emerald-700 border-emerald-300' : '' }} disabled:bg-gray-50 disabled:text-gray-300">
                        <button type="button"
                                wire:click="toggleNotOffered('{{ $currentOffer->cis_row_id }}', '{{ $position->cis_row_id }}')"
                                title="Nicht korrekt angeboten"
                                class="text-sm {{ $item->not_offered ? 'text-red-500' : 'text-gray-200 hover:text-red-400' }}">
                            <i class="fa fa-triangle-exclamation"></i>
                        </button>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-100">
                <button type="button" wire:click="previousOffer" class="btn btn-ghost btn-sm" {{ $currentOfferIndex === 0 ? 'disabled' : '' }}>
                    <i class="fa fa-arrow-left mr-1.5"></i> Vorheriges Angebot
                </button>
                <button type="button" wire:click="nextOffer" class="btn btn-primary btn-sm" {{ $currentOfferIndex === $offers->count() - 1 ? 'disabled' : '' }}>
                    Nächstes Angebot <i class="fa fa-arrow-right ml-1.5"></i>
                </button>
            </div>
        </div>
        @endif

    @else
    <div class="cis-card p-0 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="text-left text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 py-2.5 sticky left-0 bg-gray-50">Position</th>
                    <th class="text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 px-2 py-2.5 w-16">Menge</th>
                    @foreach($offers as $offer)
                    <th class="px-3 py-2.5 text-left min-w-[160px] {{ !$offer->active ? 'opacity-40' : '' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-gray-700">{{ $offer->source->name }}</span>
                            <button type="button" wire:click="toggleActive('{{ $offer->cis_row_id }}')"
                                    title="{{ $offer->active ? 'Anbieter ausschließen' : 'Anbieter reaktivieren' }}"
                                    class="text-[10px] {{ $offer->active ? 'text-gray-300 hover:text-red-500' : 'text-amber-500 hover:text-amber-700' }}">
                                <i class="fa {{ $offer->active ? 'fa-ban' : 'fa-rotate-left' }}"></i>
                            </button>
                        </div>
                        @if(!$offer->active)
                            <span class="text-[9px] text-amber-600 font-medium">Ausgeschlossen</span>
                        @elseif($offer->reference)
                            <span class="text-[9px] text-gray-400">{{ $offer->reference }}</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($positions as $position)
                <tr>
                    <td class="px-3 py-2 sticky left-0 bg-white">
                        <p class="text-sm font-medium text-gray-800">{{ $position->product->name ?? '–' }}</p>
                        @if($position->note)<p class="text-xs text-gray-400">{{ $position->note }}</p>@endif
                        @if($deviation = $deviations[$position->cis_row_id] ?? null)
                        <p class="text-[10px] text-amber-600 mt-0.5 flex items-center gap-1" title="Bestellt bei {{ $deviation['source_name'] }} für {{ number_format($deviation['awarded_price'], 2, ',', '.') }} € — günstigster Preis wäre {{ number_format($deviation['cheapest_price'], 2, ',', '.') }} € gewesen.">
                            <i class="fa fa-circle-info"></i>
                            Abweichend bestellt bei {{ $deviation['source_name'] }}
                        </p>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-center text-gray-500">{{ $position->product_count }}</td>
                    @foreach($offers as $offer)
                    @php
                        $item = $matrix[$position->cis_row_id][$offer->cis_row_id] ?? null;
                        $isCheapest = $item && !$item->not_offered && $item->price !== null
                            && $offer->active
                            && (float) $item->price === (float) ($cheapestPerPosition[$position->cis_row_id] ?? null);
                    @endphp
                    <td class="px-3 py-2 {{ !$offer->active ? 'opacity-40' : '' }} {{ $isCheapest ? 'bg-emerald-50' : '' }}">
                        @if($item)
                            <div class="flex items-center gap-1.5">
                                <div class="relative flex-1">
                                    <input type="text"
                                           value="{{ $item->not_offered ? '' : $item->price }}"
                                           {{ $item->not_offered ? 'disabled' : '' }}
                                           placeholder="Preis"
                                           wire:change="saveItemPrice('{{ $offer->cis_row_id }}', '{{ $position->cis_row_id }}', $event.target.value)"
                                           class="cis-input py-1 px-2 text-sm w-24 {{ $isCheapest ? 'font-semibold text-emerald-700 border-emerald-300' : '' }} disabled:bg-gray-50 disabled:text-gray-300">
                                </div>
                                <button type="button"
                                        wire:click="toggleNotOffered('{{ $offer->cis_row_id }}', '{{ $position->cis_row_id }}')"
                                        title="Nicht korrekt angeboten"
                                        class="text-xs {{ $item->not_offered ? 'text-red-500' : 'text-gray-200 hover:text-red-400' }}">
                                    <i class="fa fa-triangle-exclamation"></i>
                                </button>
                            </div>
                            @if($item->not_offered)
                                <p class="text-[10px] text-red-500 mt-0.5">Nicht korrekt angeboten</p>
                            @endif
                        @else
                            <span class="text-xs text-gray-300">–</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endif

    {{-- ── Angebot anlegen Modal ── --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="$set('showCreateModal', false)">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Angebot anlegen</h2>
                <button type="button" wire:click="$set('showCreateModal', false)" class="text-gray-300 hover:text-gray-600">
                    <i class="fa fa-xmark"></i>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="cis-label">Anbieter <span class="text-red-500">*</span></label>
                    <select wire:model="newSourceId" class="cis-input w-full">
                        <option value="">– auswählen –</option>
                        @foreach($availableSources as $source)
                            <option value="{{ $source->cis_row_id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                    @error('newSourceId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @if($availableSources->isEmpty())
                        <p class="mt-1 text-xs text-gray-400">Alle vorhandenen Anbieter haben bereits ein Angebot in diesem Projekt.</p>
                    @endif
                </div>
                <div>
                    <label class="cis-label">Referenz / Angebotsnummer</label>
                    <input type="text" wire:model="newReference" class="cis-input w-full">
                </div>
                <div>
                    <label class="cis-label">Angebotsdatum</label>
                    <input type="date" wire:model="newSubmittedAt" class="cis-input w-full">
                </div>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="button" wire:click="$set('showCreateModal', false)" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="createOffer" class="btn btn-primary btn-sm">Anlegen</button>
            </div>
        </div>
    </div>
    @endif
</div>
