<div>
    {{-- Toolbar --}}
    <div class="flex items-center gap-2 mb-4">
        <span class="text-xs text-gray-400">{{ $rows->total() }} Produkt(e)</span>
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

    {{-- Table --}}
    <div class="cis-table">
        <table>
            <thead>
                <tr>
                    <x-data-table.th field="name" label="Name" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('name')" :active="$filters['name'] ?? []" />
                    <x-data-table.th field="category" label="Kategorie" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('category')" :active="$filters['category'] ?? []" />
                    <x-data-table.th field="price" label="Produktpreis" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('price')" :active="$filters['price'] ?? []" />
                    <x-data-table.th field="group_price" label="Gesamtpreis" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('group_price')" :active="$filters['group_price'] ?? []" />
                    <x-data-table.th field="source" label="Lieferant" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('source')" :active="$filters['source'] ?? []" />
                    <x-data-table.th field="created_at" label="Erstellt" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('created_at')" :active="$filters['created_at'] ?? []" />
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $product)
                    <tr onclick='if(!event.target.closest(".js-toggle-children")) location.href="{{ route("product.edit", $product) }}"' class="cursor-pointer">
                        <td>
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($product->hasChild())
                                    <button type="button"
                                            class="js-toggle-children text-gray-400 hover:text-gray-600 w-4 shrink-0"
                                            onclick="event.stopPropagation(); const r=this.closest('tr').nextElementSibling; const hidden = r.style.display==='none'; r.style.display = hidden ? 'table-row' : 'none'; this.querySelector('i').classList.toggle('rotate-90', hidden);">
                                        <i class="fa fa-chevron-right text-xs transition-transform"></i>
                                    </button>
                                @endif
                                <span class="font-medium text-gray-900">{{ $product->name }}</span>
                                @if($product->isSet())
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600" title="Set: interne Bündelung, erscheint auf der Ausschreibung nicht als eigene Position">
                                        <i class="fa fa-layer-group mr-0.5"></i>Set
                                    </span>
                                @endif
                                @if($product->hasChild())
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-600" title="Verknüpfte Produkte">
                                        <i class="fa fa-boxes-stacked mr-0.5"></i>{{ $product->childs->count() }}
                                    </span>
                                @endif
                                @if($product->hasParent())
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500" title="Verknüpft bei">
                                        <i class="fa fa-link mr-0.5"></i>{{ $product->getParents()->pluck('name')->implode(', ') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="text-gray-500 text-sm">{{ $product->category?->name ?? '–' }}</td>
                        <td>
                            @if($product->isSet())
                                <span class="text-gray-400 italic">Set</span>
                            @else
                                <span class="font-medium {{ $product->price() ? 'text-gray-900' : 'text-gray-400' }}">
                                    {{ $product->priceForHumans() }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($product->hasChild())
                                <span class="font-medium text-gray-900">
                                    {{ $product->getGroupPriceForHumans() }}
                                </span>
                            @else
                                <span class="font-medium {{ $product->price() ? 'text-gray-900' : 'text-gray-400' }}">
                                    {{ $product->priceForHumans() }}
                                </span>
                            @endif
                        </td>
                        <td class="text-gray-500 text-sm">
                            {{ $product->price()?->source?->name ?? '–' }}
                        </td>
                        <td class="text-gray-500 text-sm">{{ $product->created_at->format('d.m.Y') }}</td>
                    </tr>
                    @if($product->hasChild())
                    <tr x-show="open" style="display:none" class="bg-gray-50">
                        <td colspan="6" class="py-2 px-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Verknüpfte Produkte</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($product->childs as $child)
                                    <span class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded-full bg-white border border-gray-200 text-gray-600">
                                        {{ $child->name }}
                                        <span class="text-gray-400">{{ $child->priceForHumans() }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-400">
                            <i class="fa fa-box-open text-3xl mb-3 block"></i>
                            <p class="text-sm">Keine Produkte gefunden.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->links() }}</div>
</div>
