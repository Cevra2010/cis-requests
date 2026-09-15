<div>
    @include('lager::livewire._qr-scanner')

    @unless($projectId)
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-semibold text-gray-800">Warenübersicht</h2>
            <p class="text-sm text-gray-500 mt-0.5">Bestand je Produkt, Lagerort und Projekt.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" x-data
                    @click="$store.qrScanner.launch((text) => { $wire.filterByLagerort($store.qrScanner.extractId(text)); })"
                    class="btn btn-ghost btn-sm">
                <i class="fa fa-qrcode mr-1.5"></i>Lagerort scannen
            </button>
            <a href="{{ route('lager.buchen') }}" class="btn btn-ghost btn-sm">
                <i class="fa fa-box-open mr-1.5"></i>Ware buchen
            </a>
            <a href="{{ route('lager.lagerorte') }}" class="btn btn-ghost btn-sm">
                <i class="fa fa-sitemap mr-1.5"></i>Lagerorte
            </a>
        </div>
    </div>
    @endunless

    {{-- Toolbar --}}
    <div class="flex items-center gap-2 mb-4">
        <span class="text-xs text-gray-400">{{ $rows->total() }} Position(en)</span>
        @if(count($filters))
            <button wire:click="resetAllFilters" class="btn-ghost btn-sm">
                <i class="fa fa-xmark"></i>
                Alle Filter zurücksetzen
            </button>
        @endif
        <div class="ml-auto">
            <x-data-table.per-page-select />
        </div>
    </div>

    @if(count($selected))
    <div class="flex items-center gap-3 mb-3 px-4 py-2.5 rounded-xl bg-primary-50 border border-primary-200">
        <span class="text-sm text-primary-800">{{ count($selected) }} ausgewählt</span>
        <button type="button" wire:click="openMoveModal" class="btn btn-primary btn-sm">
            <i class="fa fa-arrow-right-arrow-left mr-1.5"></i>Verschieben nach…
        </button>
        <button type="button" wire:click="clearSelection" class="text-xs text-primary-600 hover:underline ml-auto">
            Auswahl aufheben
        </button>
    </div>
    @endif

    <div class="cis-table">
        <table>
            <thead>
                <tr>
                    <th class="w-8"></th>
                    <x-data-table.th field="product" label="Produkt" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('product')" :active="$filters['product'] ?? []" />
                    <x-data-table.th field="lagerort" label="Lagerort" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('lagerort')" :active="$filters['lagerort'] ?? []" />
                    @unless($projectId)
                    <x-data-table.th field="project" label="Projekt" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('project')" :active="$filters['project'] ?? []" />
                    @endunless
                    <x-data-table.th field="quantity" label="Menge" sortable align="right"
                        :order-by="$orderBy" :order-direction="$orderDirection" />
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr wire:key="stock-{{ $row->cis_row_id }}">
                    <td>
                        <input type="checkbox" wire:click="toggleSelected('{{ $row->cis_row_id }}')"
                               @checked(in_array($row->cis_row_id, $selected, true))
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    </td>
                    <td class="text-sm font-medium text-gray-800">{{ $row->product?->name ?? '–' }}</td>
                    <td class="text-sm text-gray-600">{{ $row->lagerort?->path() ?? '–' }}</td>
                    @unless($projectId)
                    <td class="text-sm">
                        @if($row->project)
                            <a href="{{ route('project.show', $row->project->cis_row_id) }}" class="text-primary-600 hover:underline">{{ $row->project->name }}</a>
                        @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">Nicht zugeordnet</span>
                        @endif
                    </td>
                    @endunless
                    <td class="text-sm text-right tabular-nums">{{ $row->quantity }}</td>
                    <td class="text-right">
                        @if($row->project)
                        <button type="button" wire:click="release('{{ $row->cis_row_id }}')" class="btn btn-ghost btn-sm">
                            Freigeben
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $projectId ? 4 : 5 }}" class="text-center py-16 text-gray-400">
                        <i class="fa fa-boxes-stacked text-4xl mb-3 block"></i>
                        <p class="text-sm font-medium">Kein Bestand gefunden.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->links() }}</div>

    {{-- ── Verschieben-Modal ── --}}
    @if($showMoveModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-1">In Lagerort verschieben</h3>
            <p class="text-xs text-gray-500 mb-4">{{ count($selected) }} Position(en) werden verschoben.</p>

            <div class="flex items-center gap-2" x-data>
                <select wire:model="moveTargetId" class="cis-input flex-1 text-sm @error('moveTargetId') is-invalid @enderror">
                    <option value="">— Ziel-Lagerort wählen —</option>
                    @foreach($lagerorte as $l)
                        <option value="{{ $l->cis_row_id }}">{{ str_repeat('— ', $l->depth) }}{{ $l->name }}</option>
                    @endforeach
                </select>
                <button type="button"
                        @click="$store.qrScanner.launch((text) => { $wire.set('moveTargetId', $store.qrScanner.extractId(text)); })"
                        title="QR-Code scannen" class="btn btn-ghost btn-sm shrink-0">
                    <i class="fa fa-qrcode"></i>
                </button>
            </div>
            @error('moveTargetId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

            <div class="flex items-center justify-end gap-2 mt-6">
                <button type="button" wire:click="$set('showMoveModal', false)" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="confirmMove" class="btn btn-primary btn-sm">Verschieben</button>
            </div>
        </div>
    </div>
    @endif
</div>
