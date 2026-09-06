@php
use CisFoundation\CisCategoryManager\CisCategoryManager;
$categories = CisCategoryManager::forType($type);
$options = $categories->map(fn ($c) => ['id' => (string) $c->id, 'label' => str_repeat('— ', $c->depth) . $c->name])->values();
$selected = $categories->first(fn ($c) => (string) $c->id === (string) ($value ?? ''));
$selectedLabel = $selected ? str_repeat('— ', $selected->depth) . $selected->name : '';
@endphp

<div x-data="{
        open: false, q: '',
        value: @js((string) ($value ?? '')),
        label: @js($selectedLabel),
        options: @js($options),
        select(opt) { this.value = opt.id; this.label = opt.label; this.open = false; this.q = ''; },
        clear() { this.value = ''; this.label = ''; this.open = false; this.q = ''; },
        filtered() { return this.options.filter(o => this.q === '' || o.label.toLowerCase().includes(this.q.toLowerCase())); },
     }"
     @click.outside="open = false"
     class="relative">
    <input type="hidden" name="{{ $name }}" :value="value">

    <button type="button" @click="open = !open; $nextTick(() => $refs.search?.focus())"
            {{ $attributes->merge(['class' => 'cis-input w-full flex items-center justify-between text-left']) }}>
        <span x-text="label || '— Keine Kategorie —'" :class="label ? 'text-gray-900' : 'text-gray-400'"></span>
        <i class="fa fa-chevron-down text-xs text-gray-400 shrink-0 ml-2"></i>
    </button>

    <div x-show="open" x-cloak
         class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg"
         style="display:none">
        <div class="p-2 border-b border-gray-100">
            <input type="text" x-ref="search" x-model="q" placeholder="Kategorie suchen…"
                   class="cis-input py-1 text-sm w-full" @click.stop @keydown.escape="open = false">
        </div>
        <div class="max-h-56 overflow-y-auto py-1">
            @unless($required ?? false)
                <button type="button" @click="clear()"
                        class="w-full text-left px-3 py-1.5 text-sm text-gray-400 hover:bg-gray-50">
                    — Keine Kategorie —
                </button>
            @endunless
            <template x-for="opt in filtered()" :key="opt.id">
                <button type="button" @click="select(opt)" x-text="opt.label"
                        class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-50"
                        :class="opt.id === value ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700'">
                </button>
            </template>
            <p x-show="filtered().length === 0" class="px-3 py-2 text-xs text-gray-400">Keine Kategorie gefunden.</p>
        </div>
    </div>
</div>

@if($categories->isEmpty())
    <p class="mt-1 text-xs text-amber-600">
        <i class="fa fa-triangle-exclamation mr-1"></i>
        Noch keine Kategorien vom Typ „{{ CisCategoryManager::getTypeLabel($type) }}" angelegt.
        <a href="{{ route('category.index', ['type' => $type]) }}" class="underline">Jetzt anlegen</a>
    </p>
@endif
