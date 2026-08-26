@extends('layout.app')

@section('title', $parent ? 'Verknüpftes Produkt erstellen' : 'Neues Produkt')

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        @if($parent)
            <div class="flex items-center gap-2 mb-4 text-sm text-gray-500">
                <i class="fa fa-arrow-turn-down-right text-gray-400"></i>
                Verknüpftes Produkt von
                <a href="{{ route('product.edit', $parent) }}"
                   class="font-medium text-gray-700 hover:text-primary-600">{{ $parent->name }}</a>
            </div>
        @endif

        <h2 class="text-base font-semibold text-gray-900 mb-4">
            {{ $parent ? 'Verknüpftes Produkt anlegen' : 'Neues Produkt anlegen' }}
        </h2>

        <form action="{{ route('product.store') }}" method="POST" class="space-y-4">
            @csrf
            @if($parent)
                <input type="hidden" name="parent" value="{{ $parent->cis_row_id }}">
            @endif

            <div>
                <label class="cis-label" for="name">Produktname <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name') }}" required
                       placeholder="{{ $parent ? 'z.B. Pumpe PN 10-2000' : 'z.B. Hohlstrahlrohr C' }}">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="cis-label" for="category_id">Kategorie</label>
                <x-cis-category-select type="product.category" name="category_id" :value="old('category_id')" />
            </div>

            @unless($parent)
            <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="is_set" value="1" class="mt-0.5" {{ old('is_set') ? 'checked' : '' }}>
                <span>
                    Als Set anlegen
                    <span class="block text-xs text-gray-400">Dient nur der internen Bündelung mehrerer Produkte, hat keinen eigenen Preis/Beschreibungstext und erscheint auf der Ausschreibung nicht als eigene Position — nur seine Mitgliedsprodukte.</span>
                </span>
            </label>
            @endunless

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Produkt erstellen</button>
                <a href="{{ $parent ? route('product.edit', $parent) : route('product') }}"
                   class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
