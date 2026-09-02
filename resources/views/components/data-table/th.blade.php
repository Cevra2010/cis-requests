@props([
    'field' => null,
    'label' => '',
    'sortable' => false,
    'filterable' => false,
    'orderBy' => '',
    'orderDirection' => 'ASC',
    'options' => [],
    'active' => [],
    'align' => 'left',
])

<th {{ $attributes->merge(['class' => $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : '')]) }}>
    <div class="flex items-center gap-1 {{ $align === 'right' ? 'justify-end' : ($align === 'center' ? 'justify-center' : '') }}">
        @if($sortable)
            <button type="button" wire:click='order("{{ $field }}")' class="flex items-center gap-1 select-none hover:text-gray-700">
                <span>{{ $label }}</span>
                @if($orderBy === $field)
                    <i class="fa fa-arrow-{{ $orderDirection === 'ASC' ? 'down' : 'up' }}-wide-short text-primary-400"></i>
                @else
                    <i class="fa fa-sort text-gray-300"></i>
                @endif
            </button>
        @else
            <span>{{ $label }}</span>
        @endif

        @if($filterable)
            {{--
                Das Popover wird per x-teleport ans Ende von <body> verschoben und
                per position:fixed (aus der Trigger-Position berechnet) platziert.
                Grund: .cis-table hat overflow-hidden (für die abgerundeten Ecken) –
                das schneidet JEDES Kind-Element ab, auch position:fixed/absolute,
                solange es im selben DOM-Zweig bleibt. Bei kurzen Tabellen (wenige
                Zeilen) würde das Popover dadurch unten abgeschnitten. Ein reiner
                z-index löst das nicht, nur das Herauslösen aus der Tabelle per Teleport.
            --}}
            <div x-data="{
                    open: false, q: '', top: 0, left: 0,
                    show() {
                        const r = $refs.trigger.getBoundingClientRect();
                        this.top  = r.bottom + 4;
                        this.left = {{ $align === 'right' ? 'r.right - 224' : 'r.left' }};
                        this.left = Math.max(8, Math.min(this.left, window.innerWidth - 232));
                        this.open = true;
                    },
                 }"
                 @scroll.window="open = false"
                 @resize.window="open = false"
                 class="relative">
                <button type="button" x-ref="trigger" @click="open ? (open = false) : show()"
                        class="w-5 h-5 flex items-center justify-center rounded hover:bg-gray-200 {{ count($active) ? 'text-primary-600' : 'text-gray-300' }}"
                        title="Filtern">
                    <i class="fa fa-filter text-xs"></i>
                    @if(count($active))
                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-primary-600 text-white text-[9px] leading-[14px] text-center">{{ count($active) }}</span>
                    @endif
                </button>

                <template x-teleport="body">
                    <div x-show="open"
                         @click.outside="if (! $refs.trigger.contains($event.target)) open = false"
                         :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                         class="z-50 w-56 rounded-lg border border-gray-200 bg-white shadow-lg normal-case font-normal text-gray-700"
                         style="display:none">
                        <div class="p-2 border-b border-gray-100">
                            <input type="text" x-model="q" placeholder="Suchen…"
                                   class="cis-input py-1 text-xs" @click.stop>
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1">
                            @forelse($options as $value => $optionLabel)
                                <label x-show="q === '' || '{{ Str::of((string) $optionLabel)->lower()->replace("'", "\\'") }}'.includes(q.toLowerCase())"
                                       class="flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox"
                                           {{ in_array((string) $value, $active, true) ? 'checked' : '' }}
                                           wire:click="toggleFilterValue('{{ $field }}', '{{ $value }}')"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="truncate">{{ $optionLabel }}</span>
                                </label>
                            @empty
                                <p class="px-3 py-2 text-xs text-gray-400">Keine Werte vorhanden.</p>
                            @endforelse
                        </div>
                        @if(count($active))
                            <div class="p-2 border-t border-gray-100">
                                <button type="button" wire:click="clearColumnFilter('{{ $field }}')"
                                        class="text-xs text-gray-500 hover:text-gray-800">
                                    <i class="fa fa-xmark mr-1"></i>Filter zurücksetzen
                                </button>
                            </div>
                        @endif
                    </div>
                </template>
            </div>
        @endif
    </div>
</th>
