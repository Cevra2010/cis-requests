<div>
    @if(! $canEdit)
    <div class="mb-4 px-4 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-700 flex items-center gap-2">
        <i class="fa fa-lock"></i>
        Die Ausschreibung ist fixiert. Die Fahrzeug-Konfiguration kann nicht mehr verändert werden.
    </div>
    @endif

    <div class="space-y-4">
        @forelse($blocks as $block)
        <div wire:key="block-{{ $block->cis_row_id }}" class="cis-card p-0 overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-col gap-0.5 shrink-0">
                    <button type="button" wire:click="moveBlockUp('{{ $block->cis_row_id }}')" @if(! $canEdit) disabled @endif
                            class="text-gray-300 hover:text-gray-600 disabled:opacity-30"><i class="fa fa-chevron-up text-[10px]"></i></button>
                    <button type="button" wire:click="moveBlockDown('{{ $block->cis_row_id }}')" @if(! $canEdit) disabled @endif
                            class="text-gray-300 hover:text-gray-600 disabled:opacity-30"><i class="fa fa-chevron-down text-[10px]"></i></button>
                </div>
                <input type="text" value="{{ $block->title }}" @if(! $canEdit) disabled @endif
                       wire:change="renameBlock('{{ $block->cis_row_id }}', $event.target.value)"
                       class="flex-1 min-w-0 bg-transparent border-0 text-sm font-semibold text-gray-800 focus:ring-0 focus:outline-none px-0">
                @if($canEdit)
                <button type="button" wire:click="removeBlock('{{ $block->cis_row_id }}')" wire:confirm="Block „{{ $block->title }}“ inklusive Inhalt löschen?"
                        class="text-gray-300 hover:text-red-500 shrink-0">
                    <i class="fa fa-trash text-sm"></i>
                </button>
                @endif
            </div>

            <div class="divide-y divide-gray-50">
                @forelse($block->items as $item)
                <div wire:key="item-{{ $item->cis_row_id }}" class="flex items-start gap-2 px-4 py-2.5">
                    <div class="flex flex-col gap-0.5 shrink-0 pt-1.5">
                        <button type="button" wire:click="moveItemUp('{{ $block->cis_row_id }}', '{{ $item->cis_row_id }}')" @if(! $canEdit) disabled @endif
                                class="text-gray-300 hover:text-gray-600 disabled:opacity-30"><i class="fa fa-chevron-up text-[9px]"></i></button>
                        <button type="button" wire:click="moveItemDown('{{ $block->cis_row_id }}', '{{ $item->cis_row_id }}')" @if(! $canEdit) disabled @endif
                                class="text-gray-300 hover:text-gray-600 disabled:opacity-30"><i class="fa fa-chevron-down text-[9px]"></i></button>
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($item->source_label)
                            <span class="inline-flex items-center gap-1 text-[10px] text-primary-600 mb-1">
                                <i class="fa fa-link"></i> aus Fahrzeugparameter-Katalog: {{ $item->source_label }}
                            </span>
                        @endif
                        <textarea rows="2" @if(! $canEdit) disabled @endif
                                  wire:change="updateItemText('{{ $item->cis_row_id }}', $event.target.value)"
                                  x-data="{ resize(el) { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; } }"
                                  x-init="$nextTick(() => resize($el))" @input="resize($el)"
                                  style="min-height: 3.25rem;"
                                  class="cis-input w-full text-sm leading-relaxed resize-none overflow-hidden">{{ $item->text }}</textarea>
                    </div>
                    @if($canEdit)
                    <button type="button" wire:click="removeItem('{{ $item->cis_row_id }}')" class="text-gray-300 hover:text-red-500 shrink-0 pt-1.5">
                        <i class="fa fa-xmark"></i>
                    </button>
                    @endif
                </div>
                @empty
                <p class="px-4 py-6 text-center text-xs text-gray-400">Noch kein Inhalt in diesem Block.</p>
                @endforelse
            </div>

            @if($canEdit)
            <div class="flex items-center gap-2 px-4 py-2.5 border-t border-gray-50 bg-gray-50/30">
                <button type="button" wire:click="addTextItem('{{ $block->cis_row_id }}')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-plus mr-1.5"></i> Text
                </button>
                <button type="button" wire:click="openParameterBrowser('{{ $block->cis_row_id }}')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-book mr-1.5"></i> Aus Fahrzeugparameter-Katalog übernehmen
                </button>
            </div>
            @endif
        </div>
        @empty
        <div class="cis-card text-center py-14 text-gray-400">
            <i class="fa fa-sliders text-3xl mb-2 block"></i>
            <p class="text-sm">Noch keine Blöcke angelegt.</p>
            <p class="text-xs mt-1">Blöcke gliedern die Fahrzeug-Konfiguration, z.B. „Fahrgestell", „Aufbau".</p>
        </div>
        @endforelse

        @if($canEdit)
        <button type="button" wire:click="addBlock" class="btn btn-primary btn-sm">
            <i class="fa fa-plus mr-1.5"></i> Neuer Block
        </button>
        @endif
    </div>

    {{-- ── Parameter-Browser ── --}}
    @if($showParameterBrowser)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Fahrzeugparameter-Katalog durchsuchen</h3>
                <button type="button" wire:click="closeParameterBrowser" class="text-gray-400 hover:text-gray-700">
                    <i class="fa fa-xmark"></i>
                </button>
            </div>
            <div class="px-6 py-3 border-b border-gray-100">
                <input type="text" wire:model.live.debounce.300ms="parameterSearch" placeholder="Fahrzeugparameter durchsuchen…"
                       class="cis-input w-full">
            </div>
            <div class="flex-1 overflow-y-auto px-4 py-3">
                @if($parameterResults !== null)
                    @forelse($parameterResults as $p)
                    <div wire:key="pbrowse-flat-{{ $p->cis_row_id }}" class="flex items-center gap-2 py-2 px-2 rounded-lg hover:bg-gray-50">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-gray-800">{{ $p->name }}</span>
                            @if($p->category)<span class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ $p->category->name }}</span>@endif
                            @if($p->description)<p class="text-xs text-gray-400 truncate">{{ $p->description }}</p>@endif
                        </div>
                        <button type="button" wire:click="insertParameter('{{ $p->cis_row_id }}')" class="btn btn-primary btn-sm shrink-0">Übernehmen</button>
                    </div>
                    @empty
                    <p class="text-center py-10 text-gray-400 text-sm">Keine Treffer.</p>
                    @endforelse
                @else
                    @forelse($parameterTree as $node)
                        @include('livewire.project._parameter-browser-node', ['node' => $node, 'depth' => 0])
                    @empty
                    <p class="text-center py-10 text-gray-400 text-sm">
                        Noch keine Fahrzeugparameter angelegt.
                        <a href="{{ route('parameter.index') }}" class="text-primary-600 hover:underline">Jetzt anlegen</a>.
                    </p>
                    @endforelse
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
