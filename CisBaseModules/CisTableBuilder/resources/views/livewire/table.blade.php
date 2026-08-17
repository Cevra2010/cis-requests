<div>
    {{-- ── Such-Bar ── --}}
    @if($def->search)
    <div class="flex items-center gap-2 mb-4">
        <div class="relative flex-1 max-w-sm">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fa fa-magnifying-glass text-gray-400 text-sm"></i>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Suchen…"
                   class="cis-input pl-9">
        </div>
        @if($search)
            <button wire:click="$set('search', '')" class="btn-ghost btn-sm">
                <i class="fa fa-xmark"></i>
                Zurücksetzen
            </button>
        @endif
    </div>
    @endif

    {{-- ── Tabelle ── --}}
    <div class="{{ $def->getCssClass() }}">
        <table>
            <thead>
                <tr>
                    @foreach($columns as $col)
                        <th @if($col->getWidth()) style="width: {{ $col->getWidth() }}" @endif>
                            @if($col->isSortable())
                                <button wire:click="sort('{{ $col->getKey() }}')"
                                        class="flex items-center gap-1 hover:text-gray-700 transition-colors">
                                    {{ $col->getLabel() }}
                                    @if($orderBy === $col->getKey())
                                        <i class="fa fa-arrow-{{ $direction === 'ASC' ? 'down' : 'up' }}-wide-short text-primary-400"></i>
                                    @else
                                        <i class="fa fa-sort text-gray-300"></i>
                                    @endif
                                </button>
                            @else
                                {{ $col->getLabel() }}
                            @endif
                        </th>
                    @endforeach
                    @if($actions->count())
                        <th class="text-right">Aktionen</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    @php $clickUrl = $def->getRowClickUrl($row); @endphp
                    <tr @if($clickUrl) onclick="location.href='{{ $clickUrl }}'" class="cursor-pointer" @endif>
                        @foreach($columns as $col)
                            <td>{!! $col->renderCell($row) !!}</td>
                        @endforeach
                        @if($actions->count())
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @foreach($actions as $action)
                                        {!! $action->getLink($row) !!}
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columns->count() + ($actions->count() ? 1 : 0) }}"
                            class="text-center py-12 text-gray-400">
                            <i class="fa fa-inbox text-3xl mb-2 block"></i>
                            <p class="text-sm">Keine Einträge gefunden.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Pagination ── --}}
    @if($def->pagination && method_exists($data, 'links'))
        <div class="mt-4">
            {{ $data->links() }}
        </div>
    @endif
</div>
