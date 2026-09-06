<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-semibold text-gray-800">Zuordnung & Bestelllisten</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                Mindestbestellwert dieses Projekts:
                <strong>{{ number_format($project->effectiveMinOrderValue(), 2, ',', '.') }} €</strong>
                @if(is_null($project->min_order_value))
                    <span class="text-gray-400">(globaler Standard)</span>
                @endif
            </p>
        </div>
        <button type="button" wire:click="computeSuggestions" class="btn btn-primary btn-sm">
            <i class="fa fa-wand-magic-sparkles mr-1.5"></i> Vorschlag berechnen
        </button>
    </div>

    {{-- ── Interne Beschaffung: nicht-ausschreibungsrelevante Positionen (feste Quelle) ── --}}
    @if($internalPositions->isNotEmpty())
    <div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 overflow-hidden">
        <div class="px-4 py-3 flex items-center justify-between gap-4 border-b border-sky-100">
            <div>
                <p class="text-sm font-semibold text-sky-800"><i class="fa fa-house mr-1.5"></i>Interne Beschaffung</p>
                <p class="text-xs text-sky-700 mt-0.5">
                    Feste, nicht-ausschreibungsrelevante Quelle statt Angebot – wird regulär geplant, aber nicht ausgeschrieben.
                </p>
            </div>
            <a href="{{ route('project.material-request.pdf', $project->cis_row_id) }}" target="_blank"
               class="btn btn-ghost btn-sm shrink-0">
                <i class="fa fa-file-pdf mr-1.5"></i>Materialanforderung
            </a>
        </div>
        <div class="divide-y divide-sky-100">
            @foreach($internalPositions->groupBy(fn($p) => $p->product->source?->name ?? '–') as $sourceName => $group)
            <div class="px-4 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-widest text-sky-600 mb-1.5">{{ $sourceName }}</p>
                @foreach($group as $item)
                <div class="flex items-center justify-between text-sm py-0.5">
                    <span class="text-gray-700">{{ $item->product->name }} <span class="text-gray-400">× {{ $item->product_count }}</span></span>
                    <span class="text-gray-500">{{ number_format($project->effectiveGroupPrice($item->product), 2, ',', '.') }} €/Stk.</span>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Konflikte ── --}}
    @if($conflicts->isNotEmpty())
    <div class="space-y-2 mb-5">
        @foreach($conflicts as $c)
        <div class="flex items-center justify-between gap-4 px-4 py-3 rounded-xl border border-amber-200 bg-amber-50">
            <div class="flex items-center gap-3">
                <i class="fa fa-circle-info text-amber-500"></i>
                <div>
                    <p class="text-sm font-medium text-amber-800">
                        Info – Mindestwert nicht erreicht: {{ $c['offer']->source->name }}
                    </p>
                    <p class="text-xs text-amber-700">
                        Aktuelle Summe {{ number_format($c['total'], 2, ',', '.') }} €
                        von {{ number_format($c['min_value'], 2, ',', '.') }} €
                        (Differenz {{ number_format($c['min_value'] - $c['total'], 2, ',', '.') }} €)
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" wire:click="excludeOffer('{{ $c['offer']->cis_row_id }}')"
                        class="btn btn-ghost btn-sm">
                    <i class="fa fa-ban mr-1.5"></i>Anbieter ausschließen
                </button>
                <button type="button" wire:click="ignoreMinValue('{{ $c['offer']->cis_row_id }}')"
                        class="btn btn-ghost btn-sm">
                    <i class="fa fa-check mr-1.5"></i>Mindestwert ignorieren
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Positionstabelle ── --}}
    <div class="cis-card p-0 overflow-x-auto mb-6">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="px-3 py-2.5 w-8"></th>
                    <th class="text-left text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 py-2.5">Position</th>
                    <th class="text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 px-2 py-2.5 w-16">Menge</th>
                    <th class="text-left text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 py-2.5">Zugeordneter Anbieter</th>
                    <th class="text-right text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 py-2.5">Preis</th>
                    <th class="px-3 py-2.5 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($positions as $position)
                @php
                    $award = $position->award;
                    $validItems = $position->offerItems
                        ->filter(fn($i) => $i->offer && $i->offer->active && !$i->not_offered && $i->price !== null)
                        ->sortBy('price');
                    $awardedItem = $award?->cis_row_id_offer
                        ? $position->offerItems->firstWhere('cis_row_id_offer', $award->cis_row_id_offer)
                        : null;

                    // Status: fehlend (kein valides Angebot) / uneindeutig (Preis-Gleichstand
                    // beim günstigsten Angebot) / eindeutig (ein klar günstigstes Angebot).
                    $posStatus = $validItems->isEmpty()
                        ? 'missing'
                        : (\App\Services\AwardCalculator::isTiedAtCheapest($validItems) ? 'tied' : 'unique');
                @endphp
                <tr>
                    <td class="px-3 py-2 text-center">
                        @if($posStatus === 'unique')
                            <i class="fa fa-circle-check text-emerald-500" title="Eindeutig günstigstes Angebot"></i>
                        @elseif($posStatus === 'tied')
                            <i class="fa fa-triangle-exclamation text-amber-500" title="Mehrere Angebote zum gleichen Preis — nicht eindeutig"></i>
                        @else
                            <i class="fa fa-circle-xmark text-red-500" title="Kein valides Angebot vorhanden"></i>
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        <p class="text-sm font-medium text-gray-800">{{ $position->product->name ?? '–' }}</p>
                    </td>
                    <td class="px-2 py-2 text-center text-gray-500">{{ $position->product_count }}</td>
                    <td class="px-3 py-2">
                        @if($validItems->isEmpty())
                            <span class="text-xs text-gray-300">Kein valides Angebot</span>
                        @else
                            <select wire:change="assignManual('{{ $position->cis_row_id }}', $event.target.value)"
                                    class="cis-input py-1 px-2 text-sm">
                                <option value="">– kein Anbieter –</option>
                                @foreach($validItems as $vi)
                                    <option value="{{ $vi->cis_row_id_offer }}"
                                        {{ $award && $award->cis_row_id_offer === $vi->cis_row_id_offer ? 'selected' : '' }}>
                                        {{ $vi->offer->source->name }} — {{ number_format($vi->price, 2, ',', '.') }} €
                                    </option>
                                @endforeach
                            </select>
                            @if($award?->is_manual_override)
                                <span class="text-[10px] text-amber-600 ml-1"><i class="fa fa-hand"></i> manuell</span>
                            @endif
                        @endif
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700">
                        @if($awardedItem)
                            {{ number_format($awardedItem->price, 2, ',', '.') }} €
                        @else
                            <span class="text-gray-300">–</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($award?->is_manual_override)
                        <button type="button" wire:click="resetToSuggestion('{{ $position->cis_row_id }}')"
                                title="Auf Vorschlag zurücksetzen"
                                class="text-gray-300 hover:text-primary-600">
                            <i class="fa fa-rotate-left text-xs"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Unterprodukte (projektweit aggregiert) ── --}}
    @if($childPositions->isNotEmpty())
    <h3 class="text-sm font-semibold text-gray-700 mb-2">Verknüpfte Produkte</h3>
    <div class="cis-card p-0 overflow-x-auto mb-6">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="px-3 py-2.5 w-8"></th>
                    <th class="text-left text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 py-2.5">Verknüpftes Produkt</th>
                    <th class="text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 px-2 py-2.5 w-16">Menge</th>
                    <th class="text-left text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 py-2.5">Zugeordneter Anbieter</th>
                    <th class="text-right text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 py-2.5">Preis</th>
                    <th class="px-3 py-2.5 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($childPositions as $childPosition)
                @php
                    $childProduct = $childPosition['product'];
                    $fromSet      = $childPosition['from_set_only'] ?? false;
                    $childAward   = $childAwards->get($childProduct->cis_row_id);
                    $childItems   = $childOfferItems->get($childProduct->cis_row_id, collect());
                    $validChildItems = $childItems
                        ->filter(fn($i) => $i->offer && $i->offer->active && !$i->not_offered && $i->price !== null)
                        ->sortBy('price');
                    $awardedChildItem = $childAward?->cis_row_id_offer
                        ? $childItems->firstWhere('cis_row_id_offer', $childAward->cis_row_id_offer)
                        : null;

                    $childStatus = $validChildItems->isEmpty()
                        ? 'missing'
                        : (\App\Services\AwardCalculator::isTiedAtCheapest($validChildItems) ? 'tied' : 'unique');
                @endphp
                <tr>
                    <td class="px-3 py-2 text-center">
                        @if($childStatus === 'unique')
                            <i class="fa fa-circle-check text-emerald-500" title="Eindeutig günstigstes Angebot"></i>
                        @elseif($childStatus === 'tied')
                            <i class="fa fa-triangle-exclamation text-amber-500" title="Mehrere Angebote zum gleichen Preis — nicht eindeutig"></i>
                        @else
                            <i class="fa fa-circle-xmark text-red-500" title="Kein valides Angebot vorhanden"></i>
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @unless($fromSet)
                        <p class="text-[10px] font-bold uppercase tracking-wide text-amber-600 mb-0.5">Verknüpftes Produkt</p>
                        @endunless
                        <p class="text-sm font-medium text-gray-800">{{ $childProduct->name }}</p>
                    </td>
                    <td class="px-2 py-2 text-center text-gray-500">{{ $childPosition['quantity'] }}</td>
                    <td class="px-3 py-2">
                        @if($validChildItems->isEmpty())
                            <span class="text-xs text-gray-300">Kein valides Angebot</span>
                        @else
                            <select wire:change="assignManualChild('{{ $childProduct->cis_row_id }}', $event.target.value)"
                                    class="cis-input py-1 px-2 text-sm">
                                <option value="">– kein Anbieter –</option>
                                @foreach($validChildItems as $vi)
                                    <option value="{{ $vi->cis_row_id_offer }}"
                                        {{ $childAward && $childAward->cis_row_id_offer === $vi->cis_row_id_offer ? 'selected' : '' }}>
                                        {{ $vi->offer->source->name }} — {{ number_format($vi->price, 2, ',', '.') }} €
                                    </option>
                                @endforeach
                            </select>
                            @if($childAward?->is_manual_override)
                                <span class="text-[10px] text-amber-600 ml-1"><i class="fa fa-hand"></i> manuell</span>
                            @endif
                        @endif
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700">
                        @if($awardedChildItem)
                            {{ number_format($awardedChildItem->price, 2, ',', '.') }} €
                        @else
                            <span class="text-gray-300">–</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($childAward?->is_manual_override)
                        <button type="button" wire:click="resetChildToSuggestion('{{ $childProduct->cis_row_id }}')"
                                title="Auf Vorschlag zurücksetzen"
                                class="text-gray-300 hover:text-primary-600">
                            <i class="fa fa-rotate-left text-xs"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Bestelllisten je Anbieter ── --}}
    @if($summaries->isNotEmpty())
    <h3 class="text-sm font-semibold text-gray-700 mb-2">Bestelllisten</h3>
    <div class="grid grid-cols-2 gap-3">
        @foreach($summaries as $s)
        <div class="cis-card flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $s['offer']->source->name }}</p>
                <p class="text-xs text-gray-400">
                    {{ $s['count'] }} Position(en) &middot; {{ number_format($s['total'], 2, ',', '.') }} €
                    @if($s['offer']->min_value_ignored)
                        <span class="text-amber-600">(Mindestwert ignoriert)</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('offer.orderlist.pdf', [$project->cis_row_id, $s['offer']->cis_row_id]) }}"
               target="_blank" class="btn btn-ghost btn-sm">
                <i class="fa fa-file-pdf mr-1.5"></i>PDF
            </a>
        </div>
        @endforeach
    </div>
    @endif
</div>
