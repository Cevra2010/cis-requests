<div wire:poll.3s.visible>
    <div class="mb-5">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <p class="text-xs text-gray-400 truncate">{{ $receipt->project->name ?? '' }}</p>
                <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $receipt->offer->source->name ?? 'Wareneingang' }}</h2>
            </div>
            @if($receipt->isComplete())
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 shrink-0">
                    <i class="fa fa-circle-check"></i> Abgeschlossen
                </span>
            @endif
        </div>

        @if($otherParticipants->isNotEmpty())
        <p class="mt-1.5 text-xs text-emerald-600 flex items-center gap-1.5">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            {{ $otherParticipants->pluck('name')->filter()->join(', ') ?: $otherParticipants->count() . ' weitere Person(en)' }}
            gerade auch aktiv
        </p>
        @endif

        <div class="flex items-center gap-2 mt-3">
            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full bg-primary-600 transition-all"
                     style="width: {{ $totalCount > 0 ? round($closedCount / $totalCount * 100) : 0 }}%"></div>
            </div>
            <span class="text-xs text-gray-500 tabular-nums shrink-0">{{ $closedCount }}/{{ $totalCount }}</span>
        </div>
    </div>

    <div class="mb-4">
        @if($participant->nameIsEditable())
            <label class="cis-label text-xs">Dein Name (optional)</label>
            <input type="text" wire:model.blur="name" placeholder="z.B. Markus"
                   class="cis-input w-full text-sm">
        @else
            <p class="text-xs text-gray-400 flex items-center gap-1.5">
                <i class="fa fa-circle-user"></i>
                Angemeldet als <span class="font-medium text-gray-600">{{ $participant->displayName() }}</span>
            </p>
        @endif
    </div>

    <div class="mb-4">
        <div class="relative">
            <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Artikel suchen…"
                   class="cis-input w-full text-sm pl-8">
        </div>
    </div>

    <div class="flex items-center gap-1.5 mb-4 border border-gray-200 rounded-lg p-1 bg-gray-50">
        <button type="button" wire:click="setFilter('offen')"
                class="flex-1 text-xs font-medium py-1.5 rounded-md transition-colors {{ $filter === 'offen' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' }}">
            Offen <span class="tabular-nums">({{ $openCount }})</span>
        </button>
        <button type="button" wire:click="setFilter('abgeschlossen')"
                class="flex-1 text-xs font-medium py-1.5 rounded-md transition-colors {{ $filter === 'abgeschlossen' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' }}">
            Abgeschlossen <span class="tabular-nums">({{ $closedCount }})</span>
        </button>
        <button type="button" wire:click="setFilter('alle')"
                class="flex-1 text-xs font-medium py-1.5 rounded-md transition-colors {{ $filter === 'alle' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' }}">
            Gesamtübersicht <span class="tabular-nums">({{ $totalCount }})</span>
        </button>
    </div>

    <div class="space-y-3">
        @forelse($items as $item)
        @php
            $checked  = $item->isChecked();
            $closed   = $item->isClosed();
            $borderClass = $closed ? 'border-emerald-300' : ($checked ? 'border-amber-300' : 'border-gray-200');
        @endphp
        <div wire:key="item-{{ $item->cis_row_id }}" class="rounded-xl border-2 {{ $borderClass }} bg-white p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800">{{ $item->position->product->name ?? '–' }}</p>
                    @if($item->position?->note)
                        <p class="text-xs text-gray-400 italic">{{ $item->position->note }}</p>
                    @endif
                    <p class="text-xs text-gray-500 mt-0.5">Bestellt: <strong>{{ $item->expected_count }}</strong></p>
                    @if($item->lastParticipant)
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            <i class="fa fa-user-pen mr-0.5"></i>zuletzt von {{ $item->lastParticipant->displayName() }}
                        </p>
                    @endif
                </div>
                @if($closed)
                    <i class="fa fa-circle-check text-emerald-500 text-lg shrink-0"></i>
                @elseif($checked)
                    <i class="fa fa-triangle-exclamation text-amber-500 text-lg shrink-0"></i>
                @endif
            </div>

            <div class="mt-3 flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <button type="button" wire:click="decrement('{{ $item->cis_row_id }}')"
                            class="w-10 h-10 rounded-lg border border-gray-200 text-gray-500 text-lg leading-none active:bg-gray-100">−</button>
                    <input type="number" min="0" inputmode="numeric"
                           wire:change="setReceived('{{ $item->cis_row_id }}', $event.target.value)"
                           value="{{ $item->received_count }}"
                           placeholder="–"
                           class="w-16 text-center cis-input py-2 text-sm tabular-nums">
                    <button type="button" wire:click="increment('{{ $item->cis_row_id }}')"
                            class="w-10 h-10 rounded-lg border border-gray-200 text-gray-500 text-lg leading-none active:bg-gray-100">+</button>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" wire:click="markFull('{{ $item->cis_row_id }}')"
                            class="btn btn-ghost btn-sm !text-emerald-600">
                        <i class="fa fa-check mr-1"></i> Vollständig
                    </button>
                    @if(! $checked)
                    <button type="button" wire:click="markMissing('{{ $item->cis_row_id }}')"
                            class="btn btn-ghost btn-sm !text-red-500">
                        <i class="fa fa-xmark mr-1"></i> Fehlt
                    </button>
                    @endif
                </div>
            </div>

            <div class="mt-2" x-data="{ open: {{ $item->note ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" x-show="!open" class="text-xs text-gray-300 hover:text-gray-500">
                    <i class="fa fa-plus mr-1"></i>Anmerkung
                </button>
                <div x-show="open" style="{{ $item->note ? '' : 'display:none' }}">
                    <input type="text" wire:change="updateNote('{{ $item->cis_row_id }}', $event.target.value)"
                           value="{{ $item->note }}"
                           placeholder="z. B. beschädigt, falsche Variante…"
                           class="w-full text-xs border-0 border-b border-gray-200 focus:ring-0 focus:border-gray-400 px-0 py-1">
                </div>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 italic text-center py-8">
            @if(trim($search) !== '')
                Keine Treffer für „{{ $search }}“.
            @elseif($filter === 'offen')
                <i class="fa fa-circle-check text-emerald-400 text-lg block mb-2"></i>
                Keine offenen Positionen mehr.
            @else
                Keine Positionen vorhanden.
            @endif
        </p>
        @endforelse
    </div>

    <div class="mt-6 pt-4 border-t border-gray-100">
        @if($receipt->isComplete())
            <button type="button" wire:click="reopen" class="btn btn-ghost btn-sm w-full">
                <i class="fa fa-lock-open mr-1.5"></i> Wieder öffnen
            </button>
        @else
            <button type="button" wire:click="finish" class="btn btn-primary w-full">
                <i class="fa fa-flag-checkered mr-1.5"></i> Wareneingang abschließen
            </button>
            @if($openCount > 0)
                <p class="text-xs text-gray-400 text-center mt-2">
                    Noch {{ $openCount }} Position(en) offen.
                </p>
            @endif
        @endif
    </div>
</div>
