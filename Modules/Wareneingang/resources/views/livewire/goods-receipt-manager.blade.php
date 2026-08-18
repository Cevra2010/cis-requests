<div wire:poll.3s.visible>
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-800">Wareneingang</h2>
        <p class="text-xs text-gray-500 mt-0.5">
            Live-Übersicht des Kommissionierstands je Hersteller/Anbieter. Kommissioniert wird ausschließlich über
            die login-freien Links vom Handy — hier siehst du nur den aktuellen Stand.
        </p>
    </div>

    @if($offers->isEmpty())
    <div class="cis-card text-center py-12 text-gray-400">
        <i class="fa fa-truck-ramp-box text-3xl mb-3 block"></i>
        <p class="text-sm">Noch keine zugeordneten Bestellungen vorhanden.</p>
        <p class="text-xs text-gray-300 mt-1">Ordne Positionen zunächst im Tab „Bestellung" Anbietern zu.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($offers as $offer)
        @php
            $receipt    = $offer->receipt;
            $progress   = $receipt ? $receipt->progress() : [0, $offer->awardedCount];
            $isExpanded = $expandedOfferId === $offer->cis_row_id;
            $openItems   = $receipt ? $receipt->items->filter(fn($i) => $i->isOpen())->sortBy(fn($i) => $i->position?->sort_order ?? 0) : collect();
            $closedItems = $receipt ? $receipt->items->filter(fn($i) => $i->isClosed())->sortBy(fn($i) => $i->position?->sort_order ?? 0) : collect();
        @endphp
        <div class="cis-card" wire:key="offer-{{ $offer->cis_row_id }}">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                        <i class="fa fa-industry text-blue-500 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $offer->source->name ?? '–' }}</p>
                        <p class="text-xs text-gray-400">{{ $offer->awardedCount }} Position(en)</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if(! $receipt)
                        <button type="button" wire:click="startReceipt('{{ $offer->cis_row_id }}')" class="btn btn-primary btn-sm">
                            <i class="fa fa-clipboard-check mr-1.5"></i> Wareneingang starten
                        </button>
                    @else
                        @if($receipt->isComplete())
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                <i class="fa fa-circle-check"></i> Abgeschlossen
                            </span>
                        @elseif($receipt->hasMismatches())
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">
                                <i class="fa fa-triangle-exclamation"></i> Abweichung
                            </span>
                        @endif

                        <div class="flex items-center gap-1.5 w-28">
                            <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full bg-primary-600" style="width: {{ $progress[1] > 0 ? round($progress[0]/$progress[1]*100) : 0 }}%"></div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 tabular-nums">{{ $progress[0] }}/{{ $progress[1] }}</span>

                        <button type="button" wire:click="toggleExpanded('{{ $offer->cis_row_id }}')" class="btn btn-ghost btn-sm">
                            <i class="fa fa-{{ $isExpanded ? 'chevron-up' : 'chevron-down' }} mr-1.5"></i>
                            {{ $isExpanded ? 'Einklappen' : 'Details' }}
                        </button>
                    @endif
                </div>
            </div>

            @if($receipt && $isExpanded)
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-5">

                {{-- ── Teilnehmer / Links ── --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Kommissionier-Links</p>
                        @if($linkFormReceiptId !== $receipt->cis_row_id)
                        <button type="button" wire:click="openLinkForm('{{ $receipt->cis_row_id }}')" class="btn btn-ghost btn-sm">
                            <i class="fa fa-plus mr-1.5"></i> Neuer Link
                        </button>
                        @endif
                    </div>

                    @if($linkFormReceiptId === $receipt->cis_row_id)
                    <div class="mb-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.200ms="linkSearch"
                                   placeholder="Benutzer suchen (Name/E-Mail) oder Namen eingeben…"
                                   autocomplete="off"
                                   class="cis-input w-full text-sm">
                            @if(count($linkResults))
                            <div class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg max-h-48 overflow-y-auto">
                                @foreach($linkResults as $result)
                                <button type="button"
                                        wire:click="createParticipantForUser('{{ $receipt->cis_row_id }}', '{{ $result['id'] }}')"
                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-gray-50 transition-colors">
                                    <i class="fa fa-user text-xs text-gray-300 w-3 shrink-0"></i>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-sm text-gray-800 truncate">{{ $result['label'] }}</span>
                                        <span class="block text-[11px] text-gray-400 truncate">{{ $result['sub'] }}</span>
                                    </span>
                                    <span class="text-[10px] text-gray-300 shrink-0">Benutzer</span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between mt-2">
                            <button type="button" wire:click="createParticipantAsUnknown('{{ $receipt->cis_row_id }}')"
                                    class="text-xs text-gray-500 hover:text-gray-800">
                                <i class="fa fa-user-secret mr-1"></i>
                                @if(trim($linkSearch) !== '')
                                    Als „{{ trim($linkSearch) }}" (Unbekannt) hinzufügen
                                @else
                                    Link ohne Namen hinzufügen
                                @endif
                            </button>
                            <button type="button" wire:click="closeLinkForm" class="text-xs text-gray-400 hover:text-gray-600">
                                Abbrechen
                            </button>
                        </div>
                    </div>
                    @endif

                    @if($receipt->participants->isEmpty())
                        <p class="text-xs text-gray-400 italic">Noch keine Links erzeugt.</p>
                    @else
                        <div class="space-y-1.5">
                            @foreach($receipt->participants as $participant)
                            @php $url = route('wareneingang.public', $participant->access_token); @endphp
                            <div wire:key="participant-{{ $participant->cis_row_id }}"
                                 x-data="{ copied: false }"
                                 class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-gray-50 border border-gray-100">
                                <span class="relative flex h-2 w-2 shrink-0">
                                    @if($participant->isActive())
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    @else
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-300"></span>
                                    @endif
                                </span>
                                <span class="text-xs font-medium text-gray-700 w-28 truncate shrink-0" title="{{ $participant->cis_row_id_user ? 'System-Benutzer' : 'Unbekannt' }}">
                                    @if($participant->cis_row_id_user)
                                        <i class="fa fa-user text-primary-400 mr-1"></i>
                                    @else
                                        <i class="fa fa-user-secret text-gray-300 mr-1"></i>
                                    @endif
                                    {{ $participant->displayName() }}
                                </span>
                                <input type="text" readonly value="{{ $url }}" onclick="this.select()"
                                       class="flex-1 min-w-0 bg-transparent border-0 text-[11px] text-gray-400 outline-none truncate">
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $url }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="text-xs text-gray-400 hover:text-gray-700 shrink-0" title="Link kopieren">
                                    <i class="fa" :class="copied ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                                </button>
                                <a href="{{ $url }}" target="_blank" class="text-xs text-gray-400 hover:text-gray-700 shrink-0" title="Öffnen">
                                    <i class="fa fa-arrow-up-right-from-square"></i>
                                </a>
                                <button type="button" wire:click="removeParticipant('{{ $participant->cis_row_id }}')"
                                        wire:confirm="Diesen Link entfernen? Er funktioniert danach nicht mehr."
                                        class="text-xs text-gray-300 hover:text-red-500 shrink-0" title="Link entfernen">
                                    <i class="fa fa-trash-can"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- ── Positionen (nur Übersicht, Kommissionierung läuft über den Link) ── --}}
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">
                        Positionen — {{ $openItems->count() }} offen · {{ $closedItems->count() }} abgeschlossen
                    </p>
                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="text-left text-[10px] font-bold uppercase tracking-wider text-gray-400 px-3 py-2">Position</th>
                                    <th class="text-center text-[10px] font-bold uppercase tracking-wider text-gray-400 px-2 py-2 w-20">Bestellt</th>
                                    <th class="text-center text-[10px] font-bold uppercase tracking-wider text-gray-400 px-2 py-2 w-20">Erhalten</th>
                                    <th class="text-left text-[10px] font-bold uppercase tracking-wider text-gray-400 px-3 py-2">Zuletzt von</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($openItems->concat($closedItems) as $item)
                                <tr wire:key="ov-{{ $item->cis_row_id }}">
                                    <td class="px-3 py-2">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5
                                            {{ $item->isClosed() ? 'bg-emerald-500' : ($item->isChecked() ? 'bg-amber-500' : 'bg-gray-300') }}"></span>
                                        {{ $item->position->product->name ?? '–' }}
                                    </td>
                                    <td class="px-2 py-2 text-center text-gray-500 tabular-nums">{{ $item->expected_count }}</td>
                                    <td class="px-2 py-2 text-center tabular-nums {{ $item->isClosed() ? 'text-emerald-600 font-medium' : ($item->isChecked() ? 'text-amber-600' : 'text-gray-300') }}">
                                        {{ $item->received_count ?? '–' }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-400">
                                        {{ $item->lastParticipant?->displayName() ?? '–' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <button type="button" wire:click="resetReceipt('{{ $receipt->cis_row_id }}')"
                        wire:confirm="Wareneingang zurücksetzen? Alle Links und Prüfungen gehen verloren."
                        class="text-xs text-red-500 hover:text-red-700">
                    <i class="fa fa-rotate-left mr-1"></i> Wareneingang zurücksetzen
                </button>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
