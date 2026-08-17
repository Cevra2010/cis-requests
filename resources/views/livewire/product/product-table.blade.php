<div>
    {{-- Search bar --}}
    <div class="flex items-center gap-2 mb-4">
        <div class="relative flex-1 max-w-sm">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fa fa-magnifying-glass text-gray-400 text-sm"></i>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="searchString"
                   placeholder="Produkt suchen…"
                   class="cis-input pl-9">
        </div>
        @if($searchString)
            <button wire:click='$set("searchString", null)' class="btn-ghost btn-sm">
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
                    <tr onclick='location.href="{{ route("product.edit", $product) }}"' class="cursor-pointer">
                        <td>
                            <div class="flex items-center gap-2">
                                @if($product->hasParent())
                                    <span class="text-xs text-gray-400">[{{ $product->getParent()->name }}]</span>
                                    <i class="fa fa-chevron-right text-gray-300 text-xs"></i>
                                @endif
                                <span class="font-medium text-gray-900">{{ $product->name }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="font-medium {{ $product->price() ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $product->priceForHumans() }}
                            </span>
                        </td>
                        <td>
                            @if($product->hasChild())
                                <span class="font-medium text-gray-900">
                                    {{ $product->getGroupPriceForHumans() }}
                                </span>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>
                        <td class="text-gray-500 text-sm">
                            {{ $product->price()?->source?->name ?? '–' }}
                        </td>
                        <td class="text-gray-500 text-sm">{{ $product->created_at->format('d.m.Y') }}</td>
                    </tr>
                    @if($product->hasChild())
                        @foreach($product->getChild() as $child)
                            <tr class="bg-gray-50/50 cursor-pointer" onclick='location.href="{{ route("product.edit", $child) }}"'>
                                <td class="pl-10">
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <i class="fa fa-arrow-turn-down-right text-gray-300 text-xs"></i>
                                        {{ $child->name }}
                                    </div>
                                </td>
                                <td class="text-gray-600">
                                    {{ $child->priceForHumans() }}
                                </td>
                                <td class="text-gray-300">–</td>
                                <td class="text-gray-500 text-sm">
                                    {{ $child->price()?->source?->name ?? '–' }}
                                </td>
                                <td class="text-gray-500 text-sm">{{ $child->created_at->format('d.m.Y') }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-gray-400">
                            <i class="fa fa-box-open text-3xl mb-3 block"></i>
                            <p class="text-sm">Keine Produkte gefunden.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
