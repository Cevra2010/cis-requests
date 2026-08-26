<div>
    {{-- Search bar --}}
    <div class="flex items-center gap-2 mb-4">
        <div class="relative flex-1 max-w-lg">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                <i class="fa fa-magnifying-glass text-gray-400 text-sm"></i>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="searchString"
                   placeholder="Produkt suchen…"
                   class="cis-input pl-10 py-2.5 text-base">
        </div>
        @if($categoryOptions)
        <select wire:model.live="categoryFilter" class="cis-input py-1.5 text-sm">
            <option value="">Alle Kategorien</option>
            @foreach($categoryOptions as $catId => $catLabel)
                <option value="{{ $catId }}">{{ $catLabel }}</option>
            @endforeach
        </select>
        @endif
        @if($searchString || $categoryFilter !== '')
            <button wire:click="resetFilters" class="btn-ghost btn-sm">
                <i class="fa fa-xmark"></i>
                Zurücksetzen
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="cis-table">
        <table>
            <thead>
                <tr>
                    <th wire:click='order("name")' class="cursor-pointer select-none">
                        <span class="flex items-center gap-1">
                            Name
                            @if($orderBy === 'name')
                                <i class="fa fa-arrow-{{ $orderDirection === 'ASC' ? 'down' : 'up' }}-wide-short text-primary-400"></i>
                            @else
                                <i class="fa fa-sort text-gray-300"></i>
                            @endif
                        </span>
                    </th>
                    <th>Kategorie</th>
                    <th>Produktpreis</th>
                    <th>Gesamtpreis</th>
                    <th>Lieferant</th>
                    <th wire:click='order("created_at")' class="cursor-pointer select-none">
                        <span class="flex items-center gap-1">
                            Erstellt
                            @if($orderBy === 'created_at')
                                <i class="fa fa-arrow-{{ $orderDirection === 'ASC' ? 'down' : 'up' }}-wide-short text-primary-400"></i>
                            @else
                                <i class="fa fa-sort text-gray-300"></i>
                            @endif
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
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
</div>
