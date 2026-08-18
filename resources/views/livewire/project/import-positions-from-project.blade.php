<div>
    @if($canEdit)
    <button type="button" wire:click="openModal" class="btn btn-ghost btn-sm">
        <i class="fa fa-clone mr-1.5"></i> Vorlage aus anderem Projekt übernehmen
    </button>
    @endif

    @if($open)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="closeModal">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">

            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                <h2 class="text-sm font-semibold text-gray-800">Positionen als Vorlage übernehmen</h2>
                <button type="button" wire:click="closeModal" class="text-gray-300 hover:text-gray-600">
                    <i class="fa fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 overflow-y-auto flex-1">

                @if(!$sourceProjectId)
                    {{-- Projekt-Auswahl --}}
                    <label class="cis-label">Quellprojekt suchen</label>
                    <input type="text" wire:model.live.debounce.200ms="search"
                           placeholder="Projektname…" class="cis-input w-full mb-3">

                    <div class="divide-y divide-gray-50 border border-gray-100 rounded-lg max-h-72 overflow-y-auto">
                        @forelse($projects as $proj)
                        <button type="button"
                                wire:click="selectSourceProject('{{ $proj->cis_row_id }}')"
                                class="w-full flex items-center justify-between px-3 py-2.5 text-left hover:bg-gray-50 transition-colors">
                            <span class="text-sm font-medium text-gray-800">{{ $proj->name }}</span>
                            @if($proj->tender_year)
                                <span class="text-xs text-gray-400">{{ $proj->tender_year }}</span>
                            @endif
                        </button>
                        @empty
                        <p class="px-3 py-6 text-sm text-gray-400 text-center">Kein Projekt gefunden.</p>
                        @endforelse
                    </div>
                @else
                    {{-- Positions-Auswahl --}}
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs text-gray-500">
                            {{ count($selected) }} von {{ $sourcePositions->count() }} Position(en) ausgewählt
                        </p>
                        <button type="button" wire:click="$set('sourceProjectId', null)" class="text-xs text-primary-600 hover:underline">
                            <i class="fa fa-arrow-left mr-1"></i>Anderes Projekt wählen
                        </button>
                    </div>

                    <div class="divide-y divide-gray-50 border border-gray-100 rounded-lg">
                        @forelse($sourcePositions as $pos)
                        @php $isSelected = array_key_exists($pos->cis_row_id_product, $selected); @endphp
                        <div class="flex items-center gap-3 px-3 py-2.5">
                            <button type="button" wire:click="toggle('{{ $pos->cis_row_id_product }}')"
                                    class="w-4 h-4 rounded border flex items-center justify-center shrink-0
                                           {{ $isSelected ? 'bg-primary-600 border-primary-600' : 'border-gray-300 bg-white' }}">
                                @if($isSelected)<i class="fa fa-check text-white" style="font-size:8px"></i>@endif
                            </button>
                            <span class="flex-1 text-sm {{ $isSelected ? 'text-gray-800 font-medium' : 'text-gray-400' }}">
                                {{ $pos->name }}
                            </span>
                            @if($isSelected)
                            <input type="number" min="1" value="{{ $selected[$pos->cis_row_id_product] }}"
                                   wire:change="updateQty('{{ $pos->cis_row_id_product }}', $event.target.value)"
                                   class="cis-input py-0.5 px-1.5 text-sm text-center w-16">
                            @endif
                        </div>
                        @empty
                        <p class="px-3 py-6 text-sm text-gray-400 text-center">Dieses Projekt hat keine Positionen.</p>
                        @endforelse
                    </div>
                @endif
            </div>

            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-3 shrink-0">
                <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm">Abbrechen</button>
                @if($sourceProjectId)
                <button type="button" wire:click="import" {{ empty($selected) ? 'disabled' : '' }}
                        class="btn btn-primary btn-sm disabled:opacity-40">
                    <i class="fa fa-check mr-1.5"></i>{{ count($selected) }} Position(en) übernehmen
                </button>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
