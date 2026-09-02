<div>
    <div class="flex items-center gap-2 mb-4">
        <span class="text-xs text-gray-400">{{ $rows->total() }} Produktquelle(n)</span>
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

    <div class="cis-table">
        <table>
            <thead>
                <tr>
                    <x-data-table.th field="name" label="Name" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('name')" :active="$filters['name'] ?? []" />
                    <x-data-table.th field="contact_name" label="Ansprechpartner" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('contact_name')" :active="$filters['contact_name'] ?? []" />
                    <x-data-table.th field="contact_email" label="E-Mail" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('contact_email')" :active="$filters['contact_email'] ?? []" />
                    <x-data-table.th field="contact_phone" label="Telefon" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('contact_phone')" :active="$filters['contact_phone'] ?? []" />
                    <x-data-table.th field="url" label="Website" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('url')" :active="$filters['url'] ?? []" />
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $source)
                <tr>
                    <td>
                        <a href="{{ route('source.edit', $source) }}"
                           class="font-medium text-gray-900 hover:text-primary-600 transition-colors">
                            {{ $source->name }}
                        </a>
                        @if($source->notes)
                            <p class="text-xs text-gray-400 truncate max-w-xs mt-0.5">{{ $source->notes }}</p>
                        @endif
                    </td>
                    <td class="text-sm text-gray-600">{{ $source->contact_name ?? '–' }}</td>
                    <td class="text-sm text-gray-500">
                        @if($source->contact_email)
                            <a href="mailto:{{ $source->contact_email }}" class="hover:text-primary-600">{{ $source->contact_email }}</a>
                        @else
                            –
                        @endif
                    </td>
                    <td class="text-sm text-gray-500">{{ $source->contact_phone ?? '–' }}</td>
                    <td class="text-sm">
                        @if($source->url)
                            <a href="{{ $source->url }}" target="_blank" rel="noopener"
                               class="text-primary-600 hover:underline truncate max-w-[180px] inline-block">
                                {{ parse_url($source->url, PHP_URL_HOST) ?? $source->url }}
                                <i class="fa fa-arrow-up-right-from-square text-[10px] ml-0.5"></i>
                            </a>
                        @else
                            <span class="text-gray-400">–</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('source.edit', $source) }}" class="btn btn-ghost btn-sm">
                                <i class="fa fa-pencil"></i>
                            </a>
                            <a href="{{ route('source.delete', $source) }}" class="btn btn-ghost btn-sm text-red-500">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-16 text-gray-400">
                        <i class="fa fa-truck text-4xl mb-3 block"></i>
                        <p class="text-sm font-medium">Keine Produktquellen gefunden.</p>
                        <a href="{{ route('source.create') }}" class="btn btn-primary btn-sm mt-4">
                            Erste Quelle erstellen
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->links() }}</div>
</div>
