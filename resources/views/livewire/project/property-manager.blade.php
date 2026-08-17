<div class="space-y-4">
    {{-- Search / Add --}}
    <div class="relative">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fa fa-magnifying-glass text-gray-400 text-sm"></i>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Eigenschaft suchen und zuordnen…"
                   class="cis-input pl-9 w-full">
        </div>

        @if(count($searchResults) > 0)
        <div class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
            @foreach($searchResults as $result)
            <button type="button"
                    wire:click="add('{{ $result['id'] }}')"
                    class="w-full flex items-center px-4 py-2.5 text-sm hover:bg-primary-50 transition-colors border-b border-gray-100 last:border-0 text-left">
                <i class="fa fa-list-check text-gray-300 mr-3 text-xs"></i>
                <span class="font-medium text-gray-900">{{ $result['name'] }}</span>
            </button>
            @endforeach
        </div>
        @elseif(strlen(trim($search)) >= 2 && count($searchResults) === 0)
        <div class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-sm px-4 py-3 text-sm text-gray-400">
            Keine Eigenschaft gefunden.
            <a href="{{ route('property.create') }}" class="text-primary-600 hover:underline ml-1">Neu anlegen</a>
        </div>
        @endif
    </div>

    {{-- Assigned properties list --}}
    @if($assigned->count())
    <div class="space-y-3">
        @foreach($assigned as $item)
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            {{-- Header row --}}
            <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                {{-- Sort buttons --}}
                <div class="flex flex-col gap-0 shrink-0">
                    <button type="button"
                            wire:click="moveUp('{{ $item->cis_row_id }}')"
                            class="text-gray-300 hover:text-gray-600 transition-colors leading-tight"
                            {{ $loop->first ? 'disabled' : '' }}>
                        <i class="fa fa-chevron-up text-xs"></i>
                    </button>
                    <button type="button"
                            wire:click="moveDown('{{ $item->cis_row_id }}')"
                            class="text-gray-300 hover:text-gray-600 transition-colors leading-tight"
                            {{ $loop->last ? 'disabled' : '' }}>
                        <i class="fa fa-chevron-down text-xs"></i>
                    </button>
                </div>

                <span class="flex-1 text-sm font-semibold text-gray-800">{{ $item->name }}</span>

                @if($item->custom_description)
                    <span class="text-xs text-amber-500 font-medium shrink-0">
                        <i class="fa fa-pen-to-square mr-1"></i>Angepasst
                    </span>
                @endif

                <button type="button"
                        wire:click="remove('{{ $item->cis_row_id }}')"
                        wire:confirm="Eigenschaft aus dem Projekt entfernen?"
                        class="text-gray-300 hover:text-red-500 transition-colors shrink-0">
                    <i class="fa fa-xmark"></i>
                </button>
            </div>

            {{-- Description textarea --}}
            <div class="px-4 py-3 bg-white">
                @if(!$item->custom_description)
                    <p class="text-xs text-gray-400 mb-1.5">Standard-Text (aus Eigenschaftsdefinition):</p>
                @else
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-xs text-amber-500">Projektspezifischer Text:</p>
                        <button type="button"
                                wire:click="resetDescription('{{ $item->cis_row_id }}')"
                                class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fa fa-rotate-left mr-1"></i>Zurücksetzen
                        </button>
                    </div>
                @endif
                <textarea
                    rows="3"
                    placeholder="{{ $item->default_description ?? 'Beschreibungstext eingeben…' }}"
                    wire:change="updateDescription('{{ $item->cis_row_id }}', $event.target.value)"
                    class="w-full text-sm text-gray-700 cis-input resize-y">{{ $item->custom_description ?? $item->default_description }}</textarea>
            </div>
        </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 text-right">{{ $assigned->count() }} Eigenschaft(en) zugeordnet</p>
    @else
    <div class="text-center py-10 text-gray-400 border border-dashed border-gray-200 rounded-lg">
        <i class="fa fa-list-check text-2xl mb-2 block"></i>
        <p class="text-sm">Noch keine Eigenschaften zugeordnet.</p>
        <p class="text-xs mt-1">Suche oben nach einer Eigenschaft.</p>
    </div>
    @endif
</div>
