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

            <div x-data="{
                    sourceId: @js((string) old('cis_row_id_source', '')),
                    sourceOpen: false, sourceQuery: '',
                    sources: @js($sources->map(fn ($s) => ['id' => $s->cis_row_id, 'name' => $s->name, 'tender_relevant' => (bool) $s->tender_relevant])),
                    get selectedSource() { return this.sources.find(s => s.id === this.sourceId) ?? null; },
                    get nonTenderRelevant() { return this.selectedSource && ! this.selectedSource.tender_relevant; },
                    filteredSources() { return this.sources.filter(s => this.sourceQuery === '' || s.name.toLowerCase().includes(this.sourceQuery.toLowerCase())); },
                 }">
                <label class="cis-label">Feste Quelle</label>
                <div class="relative" @click.outside="sourceOpen = false">
                    <button type="button" @click="sourceOpen = !sourceOpen; $nextTick(() => $refs.srcSearch?.focus())"
                            class="cis-input w-full flex items-center justify-between text-left">
                        <span x-text="selectedSource ? selectedSource.name : '— Keine feste Quelle —'"
                              :class="selectedSource ? 'text-gray-900' : 'text-gray-400'"></span>
                        <i class="fa fa-chevron-down text-xs text-gray-400 shrink-0 ml-2"></i>
                    </button>
                    <input type="hidden" name="cis_row_id_source" :value="sourceId">
                    <div x-show="sourceOpen" x-cloak
                         class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg"
                         style="display:none">
                        <div class="p-2 border-b border-gray-100">
                            <input type="text" x-ref="srcSearch" x-model="sourceQuery" placeholder="Quelle suchen…"
                                   class="cis-input py-1 text-sm w-full" @click.stop @keydown.escape="sourceOpen = false">
                        </div>
                        <div class="max-h-48 overflow-y-auto py-1">
                            <button type="button" @click="sourceId = ''; sourceOpen = false"
                                    class="w-full text-left px-3 py-1.5 text-sm text-gray-400 hover:bg-gray-50">
                                — Keine feste Quelle —
                            </button>
                            <template x-for="s in filteredSources()" :key="s.id">
                                <button type="button" @click="sourceId = s.id; sourceOpen = false"
                                        class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-50"
                                        :class="s.id === sourceId ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700'">
                                    <span x-text="s.name"></span>
                                    <span x-show="! s.tender_relevant" class="text-[9px] text-sky-500 ml-1">(nicht ausschreibungsrelevant)</span>
                                </button>
                            </template>
                            <p x-show="filteredSources().length === 0" class="px-3 py-2 text-xs text-gray-400">Keine Quelle gefunden.</p>
                        </div>
                    </div>
                </div>
                <label x-show="nonTenderRelevant" x-cloak class="flex items-center gap-1.5 mt-1.5 text-xs text-gray-500 cursor-pointer">
                    <input type="hidden" name="include_in_estimate" value="0">
                    <input type="checkbox" name="include_in_estimate" value="1" checked>
                    In Kalkulation einschließen
                </label>
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
