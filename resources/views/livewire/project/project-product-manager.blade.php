<div>

    @unless($canEdit)
    <div class="mb-4 px-4 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-700 flex items-center gap-2">
        <i class="fa fa-lock"></i>
        Die Ausschreibung ist fixiert. Produkte können nicht mehr geändert werden.
    </div>
    @endunless

    <div class="grid grid-cols-5 gap-5" style="height: 640px;">

    {{-- ── Links: Verfügbare Produkte ───────────────────────────────────────── --}}
    <div class="col-span-2 flex flex-col rounded-xl border border-gray-200 overflow-hidden {{ !$canEdit ? 'opacity-50 pointer-events-none' : '' }}">

        {{-- Header + Suche --}}
        <div class="px-4 pt-3 pb-3 border-b border-gray-100 bg-gray-50 shrink-0">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Verfügbare Produkte</p>
            <div class="relative">
                <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                <input type="text"
                       wire:model.live.debounce.200ms="search"
                       placeholder="Suchen…"
                       class="cis-input pl-8 py-1.5 text-sm w-full">
                @if($search)
                <button type="button"
                        wire:click="$set('search', '')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors">
                    <i class="fa fa-xmark text-xs"></i>
                </button>
                @endif
            </div>
        </div>

        {{-- Scrollable list --}}
        <div class="flex-1 overflow-y-auto divide-y divide-gray-50 bg-white">
            @forelse($available as $product)
            <button type="button"
                    wire:click="add('{{ $product->cis_row_id }}')"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-primary-50 transition-colors group">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate group-hover:text-primary-700">{{ $product->name }}</p>
                </div>
                <i class="fa fa-plus text-[10px] text-gray-300 group-hover:text-primary-500 transition-colors shrink-0"></i>
            </button>
            @empty
            <div class="flex flex-col items-center justify-center h-full py-12 text-gray-400">
                <i class="fa fa-magnifying-glass text-2xl mb-2"></i>
                <p class="text-sm">
                    @if($search) Kein Produkt gefunden. @else Alle Produkte bereits zugeordnet. @endif
                </p>
            </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 shrink-0">
            <p class="text-[10px] text-gray-400">
                {{ $available->count() }} {{ $search ? 'Treffer' : 'verfügbar' }}
            </p>
        </div>
    </div>

    {{-- ── Rechts: Zugeordnete Produkte ─────────────────────────────────────── --}}
    <div class="col-span-3 flex flex-col rounded-xl border border-gray-200 overflow-hidden">

        {{-- Header --}}
        <div class="px-4 pt-3 pb-3 border-b border-gray-100 bg-gray-50 shrink-0 flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Zugeordnete Produkte</p>
            <span class="text-[10px] text-gray-400">{{ $assigned->count() }} Produkt(e)</span>
        </div>

        @if($assigned->count())
        {{-- Scrollable assigned list --}}
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100 bg-white">
            @foreach($assigned as $item)
            <div class="group/row hover:bg-gray-50/60 transition-colors">

                {{-- Main row --}}
                <div class="flex items-center gap-2 px-4 py-2.5">

                    {{-- Sort --}}
                    @if($canEdit)
                    <div class="flex flex-col gap-0.5 shrink-0">
                        <button type="button"
                                wire:click="moveUp('{{ $item->cis_row_id }}')"
                                {{ $loop->first ? 'disabled' : '' }}
                                class="text-gray-300 hover:text-gray-600 disabled:opacity-25 leading-none transition-colors">
                            <i class="fa fa-chevron-up text-[10px]"></i>
                        </button>
                        <button type="button"
                                wire:click="moveDown('{{ $item->cis_row_id }}')"
                                {{ $loop->last ? 'disabled' : '' }}
                                class="text-gray-300 hover:text-gray-600 disabled:opacity-25 leading-none transition-colors">
                            <i class="fa fa-chevron-down text-[10px]"></i>
                        </button>
                    </div>
                    @endif

                    {{-- Position --}}
                    <span class="text-[10px] text-gray-300 font-mono w-4 text-center shrink-0 tabular-nums">{{ $loop->iteration }}</span>

                    {{-- Name --}}
                    <a href="{{ route('product.edit', $item->cis_row_id) }}"
                       class="flex-1 text-sm font-medium text-gray-900 hover:text-primary-600 transition-colors truncate min-w-0">
                        {{ $item->name }}
                    </a>

                    {{-- Count --}}
                    <div class="flex items-center gap-1 shrink-0">
                        <span class="text-xs text-gray-400">×</span>
                        <input type="number"
                               min="1"
                               value="{{ $item->product_count }}"
                               wire:change="updateCount('{{ $item->cis_row_id }}', $event.target.value)"
                               {{ !$canEdit ? 'disabled' : '' }}
                               class="cis-input py-0.5 px-1.5 text-sm text-center w-14 disabled:bg-gray-50">
                    </div>

                    {{-- Remove --}}
                    @if($canEdit)
                    <button type="button"
                            wire:click="remove('{{ $item->cis_row_id }}')"
                            wire:confirm="Produkt aus der Liste entfernen?"
                            class="text-gray-200 hover:text-red-400 transition-colors shrink-0 ml-1 opacity-0 group-hover/row:opacity-100">
                        <i class="fa fa-xmark"></i>
                    </button>
                    @endif
                </div>

                {{-- Note --}}
                <div class="px-4 pb-2 pl-[52px]">
                    <input type="text"
                           placeholder="Notiz / Bemerkung…"
                           value="{{ $item->note }}"
                           wire:change="updateNote('{{ $item->cis_row_id }}', $event.target.value)"
                           {{ !$canEdit ? 'disabled' : '' }}
                           class="w-full text-xs text-gray-400 bg-transparent border-0 border-b border-dashed border-gray-100 focus:border-gray-300 focus:ring-0 py-0.5 px-0 placeholder-gray-200 transition-colors">
                </div>
            </div>
            @endforeach
        </div>

        @else
        {{-- Empty state --}}
        <div class="flex-1 flex flex-col items-center justify-center text-gray-300 bg-white">
            <i class="fa fa-arrow-left text-3xl mb-3"></i>
            <p class="text-sm font-medium text-gray-400">Noch keine Produkte zugeordnet</p>
            <p class="text-xs text-gray-300 mt-1">Produkt links anklicken zum Hinzufügen</p>
        </div>
        @endif

        {{-- Footer --}}
        <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 shrink-0">
            <p class="text-[10px] text-gray-400">Reihenfolge bestimmt die Ausschreibungsstruktur</p>
        </div>
    </div>

    </div>

</div>
