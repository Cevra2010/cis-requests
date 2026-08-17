@extends('layout.app')

@section('title', $product->name)

@section('header_actions')
    @if(!$product->parent)
        <a href="{{ route('product.create', $product->cis_row_id) }}" class="btn btn-ghost btn-sm">
            <i class="fa fa-plus mr-1"></i> Unterprodukt
        </a>
    @endif
    <a href="{{ route('product.edit.delete', $product) }}" class="btn btn-danger btn-sm">
        <i class="fa fa-trash mr-1"></i> Löschen
    </a>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb für Unterprodukte --}}
    @if($product->parent)
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('product.edit', $product->getParent()) }}"
           class="hover:text-primary-600 transition-colors">
            <i class="fa fa-arrow-turn-up-left mr-1"></i>{{ $product->getParent()->name }}
        </a>
        <i class="fa fa-chevron-right text-xs text-gray-300"></i>
        <span class="text-gray-900 font-medium">{{ $product->name }}</span>
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
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </form>
                <p class="text-xs text-gray-400 mt-1.5">
                    Erstellt {{ $product->created_at->format('d.m.Y') }} ·
                    Aktualisiert {{ $product->updated_at->format('d.m.Y H:i') }}
                </p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-2xl font-bold text-gray-900">{{ $product->priceForHumans() }}</p>
                @if($product->hasChild())
                    <p class="text-xs text-gray-400">Gesamt: {{ $product->getGroupPriceForHumans() }}</p>
                @endif
            </div>
        </div>
    </div>

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

    {{-- Unterprodukte --}}
    @if($product->hasChild())
    <div class="cis-card p-0 overflow-hidden">
        <div class="px-6 py-3.5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Unterprodukte</h3>
            <a href="{{ route('product.create', $product->cis_row_id) }}" class="btn btn-ghost btn-sm">
                <i class="fa fa-plus mr-1"></i> Hinzufügen
            </a>
        </div>
        <table class="cis-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Preis</th>
                    <th>Lieferant</th>
                    <th>Aktualisiert</th>
                </tr>
            </thead>
            <tbody>
                @foreach($product->getChild()->sortBy('name') as $child)
                <tr class="cursor-pointer" onclick='location.href="{{ route("product.edit", $child) }}"'>
                    <td class="font-medium text-gray-900">{{ $child->name }}</td>
                    <td>{{ $child->priceForHumans() }}</td>
                    <td class="text-gray-500 text-sm">{{ $child->price()?->source?->name ?? '–' }}</td>
                    <td class="text-gray-400 text-sm">{{ $child->updated_at->format('d.m.Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

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
</div>
@endsection
