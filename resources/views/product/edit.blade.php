@extends('layout.app')

@section('title', $product->name)

@section('header_actions')
    @if(! $product->hasParent())
        <a href="{{ route('product.create', $product->cis_row_id) }}" class="btn btn-ghost btn-sm">
            <i class="fa fa-plus mr-1"></i> Verknüpftes Produkt
        </a>
    @endif
    <a href="{{ route('product.edit.delete', $product) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Verknüpfungen: bei welchen Produkten dieses Produkt als verknüpftes Produkt hinterlegt ist --}}
    @if($product->hasParent())
    <div class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
        <i class="fa fa-link text-xs"></i>
        <span class="text-xs text-gray-400">Verknüpft bei:</span>
        @foreach($product->getParents() as $parent)
            <span class="inline-flex items-center gap-1 bg-gray-50 border border-gray-200 rounded-full pl-2.5 pr-1 py-0.5">
                <a href="{{ route('product.edit', $parent) }}" class="hover:text-primary-600 transition-colors">
                    {{ $parent->name }}
                </a>
                <form method="POST" action="{{ route('product.child.detach', [$parent, $product]) }}"
                      onsubmit="return confirm('Verknüpfung zu „{{ addslashes($parent->name) }}“ entfernen? Beide Produkte bleiben erhalten.')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="from" value="{{ $product->cis_row_id }}">
                    <button type="submit" class="text-gray-300 hover:text-red-500 w-4 h-4 inline-flex items-center justify-center" title="Verknüpfung entfernen">
                        <i class="fa fa-xmark text-[10px]"></i>
                    </button>
                </form>
            </span>
        @endforeach
    </div>
    @endif

    {{-- Name + Meta --}}
    <div class="cis-card">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <form action="{{ route('product.edit.update', $product) }}" method="POST"
                      class="flex items-center gap-2" id="rename-form">
                    @csrf
                    <input type="text" name="name" id="product-name"
                           class="cis-input flex-1 text-base font-semibold"
                           value="{{ old('name', $product->name) }}"
                           onblur="document.getElementById('rename-form').submit()">
                    <div class="w-56 shrink-0">
                        <x-cis-category-select type="product.category" name="category_id"
                                               :value="old('category_id', $product->category_id)"
                                               onchange="document.getElementById('rename-form').submit()" />
                    </div>
                    <input type="hidden" name="is_set" value="0">
                    <label class="flex items-center gap-1.5 text-xs text-gray-500 shrink-0 cursor-pointer" title="Set: dient nur der internen Bündelung, hat keinen eigenen Preis/Beschreibungstext und erscheint auf der Ausschreibung nicht als eigene Position — nur seine Mitgliedsprodukte.">
                        <input type="checkbox" name="is_set" value="1"
                               {{ old('is_set', $product->is_set) ? 'checked' : '' }}
                               onchange="document.getElementById('rename-form').submit()">
                        Set
                    </label>
                    <div x-data="{
                            sourceId: @js((string) old('cis_row_id_source', $product->cis_row_id_source ?? '')),
                            sourceOpen: false, sourceQuery: '',
                            sources: @js($sources->map(fn ($s) => ['id' => $s->cis_row_id, 'name' => $s->name, 'tender_relevant' => (bool) $s->tender_relevant])),
                            get selectedSource() { return this.sources.find(s => s.id === this.sourceId) ?? null; },
                            get nonTenderRelevant() { return this.selectedSource && ! this.selectedSource.tender_relevant; },
                            filteredSources() { return this.sources.filter(s => this.sourceQuery === '' || s.name.toLowerCase().includes(this.sourceQuery.toLowerCase())); },
                            choose(id) { this.sourceId = id; this.sourceOpen = false; this.sourceQuery = ''; this.$nextTick(() => document.getElementById('rename-form').submit()); },
                         }"
                         class="flex items-center gap-2 shrink-0">
                        <div class="relative" @click.outside="sourceOpen = false">
                            <button type="button" @click="sourceOpen = !sourceOpen; $nextTick(() => $refs.srcSearch?.focus())"
                                    class="cis-input py-1.5 text-xs flex items-center gap-1.5" style="min-width: 150px" title="Feste Quelle: löst das frühere „Hausintern“-Merkmal ab, siehe Quellenverwaltung.">
                                <i class="fa fa-truck text-gray-400 shrink-0"></i>
                                <span x-text="selectedSource ? selectedSource.name : 'Keine feste Quelle'" class="truncate"></span>
                            </button>
                            <input type="hidden" name="cis_row_id_source" :value="sourceId">
                            <div x-show="sourceOpen" x-cloak
                                 class="absolute z-30 mt-1 w-56 rounded-lg border border-gray-200 bg-white shadow-lg"
                                 style="display:none">
                                <div class="p-2 border-b border-gray-100">
                                    <input type="text" x-ref="srcSearch" x-model="sourceQuery" placeholder="Quelle suchen…"
                                           class="cis-input py-1 text-xs w-full" @click.stop @keydown.escape="sourceOpen = false">
                                </div>
                                <div class="max-h-48 overflow-y-auto py-1">
                                    <button type="button" @click="choose('')"
                                            class="w-full text-left px-3 py-1.5 text-xs text-gray-400 hover:bg-gray-50">
                                        — Keine feste Quelle —
                                    </button>
                                    <template x-for="s in filteredSources()" :key="s.id">
                                        <button type="button" @click="choose(s.id)"
                                                class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-50"
                                                :class="s.id === sourceId ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700'">
                                            <span x-text="s.name"></span>
                                            <span x-show="! s.tender_relevant" class="text-[9px] text-sky-500 ml-1">(nicht ausschreibungsrelevant)</span>
                                        </button>
                                    </template>
                                    <p x-show="filteredSources().length === 0" class="px-3 py-2 text-xs text-gray-400">Keine Quelle gefunden.</p>
                                </div>
                            </div>
                        </div>

                        <label x-show="nonTenderRelevant" x-cloak
                               class="flex items-center gap-1.5 text-xs text-gray-500 shrink-0 cursor-pointer"
                               title="Nur bei nicht-ausschreibungsrelevanter fester Quelle: ob diese Position trotzdem in die grobe Kostenschätzung einfließt.">
                            <input type="hidden" name="include_in_estimate" value="0">
                            <input type="checkbox" name="include_in_estimate" value="1"
                                   {{ old('include_in_estimate', $product->include_in_estimate) ? 'checked' : '' }}
                                   onchange="document.getElementById('rename-form').submit()">
                            In Kalkulation einschließen
                        </label>
                    </div>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </form>
                <p class="text-xs text-gray-400 mt-1.5">
                    Erstellt {{ $product->created_at->format('d.m.Y') }} ·
                    Aktualisiert {{ $product->updated_at->format('d.m.Y H:i') }}
                </p>
            </div>
            <div class="shrink-0 text-right">
                @if($product->isSet())
                    <p class="text-2xl font-bold text-gray-900">{{ $product->getGroupPriceForHumans() }}</p>
                    <p class="text-xs text-gray-400">Set – Preis aus Mitgliedsprodukten</p>
                @else
                    <p class="text-2xl font-bold text-gray-900">{{ $product->priceForHumans() }}</p>
                    @if($product->hasChild())
                        <p class="text-xs text-gray-400">Gesamt: {{ $product->getGroupPriceForHumans() }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @unless($product->isSet())
    {{-- Beschreibung + Parameter --}}
    <div class="grid grid-cols-2 gap-5">
        <div class="cis-card">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Beschreibung</h3>
            @livewire('product.product-description-editor', ['product' => $product])
        </div>
        <div class="cis-card">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Parameter</h3>
            @livewire('product.product-parameter-editor', ['product' => $product])
        </div>
    </div>
    @endunless

    {{-- Verknüpfte Produkte --}}
    <div class="cis-card p-0 overflow-hidden">
        <div class="px-6 py-3.5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Verknüpfte Produkte</h3>
            @livewire('product.add-child', ['parent' => $product])
        </div>
        @if($product->hasChild())
        <table class="cis-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Preis</th>
                    <th>Lieferant</th>
                    <th>Aktualisiert</th>
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($product->getChild()->sortBy('name') as $child)
                <tr>
                    <td class="font-medium text-gray-900 cursor-pointer" onclick='location.href="{{ route("product.edit", $child) }}"'>
                        {{ $child->name }}
                        @if($child->getParents()->count() > 1)
                            <span class="ml-1.5 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">
                                auch bei {{ $child->getParents()->where('cis_row_id', '!=', $product->cis_row_id)->pluck('name')->implode(', ') }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $child->priceForHumans() }}</td>
                    <td class="text-gray-500 text-sm">{{ $child->price()?->source?->name ?? '–' }}</td>
                    <td class="text-gray-400 text-sm">{{ $child->updated_at->format('d.m.Y') }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('product.child.detach', [$product, $child]) }}"
                              onsubmit="return confirm('Verknüpfung zu „{{ addslashes($child->name) }}“ entfernen? Das Produkt selbst bleibt erhalten.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm text-red-500" title="Verknüpfung entfernen">
                                <i class="fa fa-link-slash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="px-6 py-8 text-center text-sm text-gray-400">Noch keine verknüpften Produkte zugeordnet.</p>
        @endif
    </div>

    @unless($product->isSet())
    {{-- Preisentwicklung --}}
    <div class="cis-card">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Preisentwicklung</h3>
        <p class="text-xs text-gray-500 mb-4">Durchschnitt über alle Lieferanten sowie je Lieferant einzeln.</p>
        <x-product-price-chart :product="$product" />
    </div>

    {{-- Preis erfassen + Preisverlauf --}}
    <div class="grid grid-cols-2 gap-5">

        {{-- Neuen Preis eintragen --}}
        <div class="cis-card">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Preis eintragen</h3>
            <form action="{{ route('product.edit.price.store', $product) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="cis-label" for="price">Preis (€)</label>
                    <input type="text" id="price" name="price" class="cis-input w-full"
                           placeholder="0,00" value="{{ old('price') }}">
                    @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="source_id">Lieferant <span class="text-red-500">*</span></label>
                    <select id="source_id" name="source_id" class="cis-input w-full" required>
                        <option value="">– Lieferant wählen –</option>
                        @foreach($sources as $src)
                            <option value="{{ $src->cis_row_id }}" {{ old('source_id') === $src->cis_row_id ? 'selected' : '' }}>
                                {{ $src->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('source_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Preis speichern</button>
            </form>

            @if($sources->isEmpty())
                <div class="mt-3 p-3 bg-amber-50 rounded-lg text-xs text-amber-700">
                    <i class="fa fa-triangle-exclamation mr-1"></i>
                    Noch keine Produktquellen angelegt.
                    <a href="{{ route('source.create') }}" class="underline">Jetzt anlegen</a>
                </div>
            @endif
        </div>

        {{-- Preisverlauf --}}
        <div class="cis-card p-0 overflow-hidden">
            <div class="px-6 py-3.5 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Preisverlauf</h3>
            </div>
            <table class="cis-table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Preis</th>
                        <th>Lieferant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prices as $price)
                    <tr>
                        <td class="text-xs text-gray-500">{{ $price->created_at->format('d.m.Y H:i') }}</td>
                        <td class="font-medium">{{ $price->amountForHumans() }}</td>
                        <td class="text-sm text-gray-500">{{ $price->source?->name ?? '–' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-8 text-gray-400 text-sm">
                            Noch kein Preis eingetragen.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
    @endunless
</div>
@endsection
