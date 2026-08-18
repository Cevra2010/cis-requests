<div class="flex border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm"
     style="min-height: 680px"
     x-data="{
         paletteSortable: null,
         tocSortable: null,
         ctx: { visible: false, x: 0, y: 0, blockId: null },

         init() {
             this.$nextTick(() => this.initSortables());
             const handler = () => this.$nextTick(() => this.initSortables());
             document.addEventListener('livewire:updated', handler);
             if (typeof this.$cleanup === 'function') {
                 this.$cleanup(() => document.removeEventListener('livewire:updated', handler));
             }
         },

         initSortables() {
             if (typeof Sortable === 'undefined') return;

             if (this.paletteSortable) this.paletteSortable.destroy();
             if (this.tocSortable)     this.tocSortable.destroy();

             const palette = this.$refs.palette;
             const tocList = this.$refs.tocList;
             if (!palette || !tocList) return;

             this.paletteSortable = Sortable.create(palette, {
                 group: { name: 'blocks', pull: 'clone', put: false },
                 sort: false,
                 animation: 150,
             });

             this.tocSortable = Sortable.create(tocList, {
                 group: { name: 'blocks', pull: true, put: true },
                 handle: '.drag-handle',
                 animation: 150,
                 onAdd: (evt) => {
                     const type = evt.item.dataset.blockType;
                     const pos  = evt.newIndex;
                     evt.item.remove();
                     $wire.addBlockAt(type, pos);
                 },
                 onEnd: (evt) => {
                     if (evt.from !== evt.to) return;
                     const ids = [...tocList.children]
                         .filter(el => el.dataset.blockId)
                         .map(el => el.dataset.blockId);
                     const doc = document.getElementById('document-blocks');
                     if (doc) ids.forEach(id => {
                         const b = document.getElementById('block-' + id);
                         if (b) doc.appendChild(b);
                     });
                     $wire.reorder(ids);
                 }
             });
         },

         showCtx(event, blockId) {
             this.ctx = { visible: true, x: event.clientX, y: event.clientY, blockId };
         },

         scrollTo(blockId) {
             document.getElementById('block-' + blockId)
                 ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
         }
     }"
     @click="ctx.visible = false"
     @keydown.escape.window="ctx.visible = false">

    {{-- ══════════════════════════════════════════════════════════════════
         LINKS — Inhaltsverzeichnis + Palette
    ══════════════════════════════════════════════════════════════════ --}}
    <aside class="w-56 shrink-0 border-r border-gray-200 bg-gray-50 flex flex-col">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-200">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Inhaltsverzeichnis</p>
        </div>

        @unless($canEdit)
        <div class="mx-3 mt-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-700 flex items-start gap-1.5">
            <i class="fa fa-lock mt-0.5"></i>
            <span>Ausschreibung fixiert – nur noch lesbar.</span>
        </div>
        @endunless

        {{-- Sortierbare Block-Liste --}}
        <div class="flex-1 overflow-y-auto py-2 px-2" x-ref="tocList">
            @forelse($blocks as $block)
            @php
                $tocIsHeading    = $block->type === 'heading';
                $tocIsText       = $block->type === 'text';
                $tocIsSpace      = $block->type === 'space';
                $tocBlockSelected = $block->config['selected'] ?? null;

                $tocIcon  = $tocIsHeading ? 'fa-heading'
                          : ($tocIsText    ? 'fa-align-left'
                          : ($tocIsSpace   ? 'fa-arrows-up-down' : 'fa-boxes-stacked'));
                $tocColor = $tocIsHeading ? 'text-indigo-500'
                          : ($tocIsText    ? 'text-violet-500'
                          : ($tocIsSpace   ? 'text-gray-400' : 'text-amber-500'));

                $rawText  = $block->config['text'] ?? '';
                $tocLabel = $tocIsHeading
                    ? ($rawText ?: 'Neue Überschrift')
                    : ($tocIsText
                        ? (Str::limit(strip_tags($rawText), 28) ?: 'Textblock')
                        : ($tocIsSpace
                            ? (($block->config['height'] ?? 40) . ' px Abstand')
                            : 'Produkte'));

                if (!$tocIsHeading && !$tocIsText && !$tocIsSpace) {
                    $tocTotal = DB::table('project_product')
                        ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
                        ->where('project_product.cis_row_id_project', $projectId)
                        ->whereNull('products.deleted_at')
                        ->count();
                    $tocShown = $tocBlockSelected === null ? $tocTotal : count($tocBlockSelected);
                }
            @endphp

            <div data-block-id="{{ $block->cis_row_id }}"
                 wire:key="toc-{{ $block->cis_row_id }}"
                 class="flex items-center gap-2 px-2 py-2 rounded-lg mb-0.5 select-none
                        hover:bg-white hover:shadow-sm transition-all"
                 @click.stop="scrollTo('{{ $block->cis_row_id }}')"
                 @contextmenu.prevent.stop="{{ $canEdit ? "showCtx(\$event, '{$block->cis_row_id}')" : '' }}">

                @if($canEdit)
                <i class="fa fa-grip-vertical drag-handle text-gray-300 hover:text-gray-500 text-xs cursor-grab active:cursor-grabbing shrink-0"></i>
                @endif
                <i class="fa {{ $tocIcon }} {{ $tocColor }} text-xs shrink-0"></i>
                <span class="text-xs text-gray-700 truncate flex-1 leading-snug">{{ $tocLabel }}</span>
                @if(!$tocIsHeading && !$tocIsText && !$tocIsSpace)
                    <span class="text-[10px] text-gray-400 tabular-nums shrink-0">{{ $tocShown }}</span>
                @endif
            </div>
            @empty
            <p class="text-xs text-gray-400 italic px-3 py-4">Noch keine Blöcke.</p>
            @endforelse
        </div>

        {{-- Palette: Blöcke per Drag & Drop hinzufügen --}}
        @if($canEdit)
        <div class="border-t border-gray-200 p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 px-1 mb-2">Hinzufügen (ziehen)</p>
            <div x-ref="palette" class="space-y-1.5">
                <div data-block-type="heading"
                     class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs font-medium
                            text-indigo-600 bg-indigo-50 hover:bg-indigo-100 cursor-grab active:cursor-grabbing select-none transition-colors">
                    <i class="fa fa-grip-vertical text-indigo-300 text-[10px]"></i>
                    <i class="fa fa-heading text-[10px]"></i>
                    Überschrift
                </div>
                <div data-block-type="products"
                     class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs font-medium
                            text-amber-600 bg-amber-50 hover:bg-amber-100 cursor-grab active:cursor-grabbing select-none transition-colors">
                    <i class="fa fa-grip-vertical text-amber-300 text-[10px]"></i>
                    <i class="fa fa-boxes-stacked text-[10px]"></i>
                    Produkte
                </div>
                <div data-block-type="text"
                     class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs font-medium
                            text-violet-600 bg-violet-50 hover:bg-violet-100 cursor-grab active:cursor-grabbing select-none transition-colors">
                    <i class="fa fa-grip-vertical text-violet-300 text-[10px]"></i>
                    <i class="fa fa-align-left text-[10px]"></i>
                    Text
                </div>
                <div data-block-type="space"
                     class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-xs font-medium
                            text-gray-500 bg-gray-100 hover:bg-gray-200 cursor-grab active:cursor-grabbing select-none transition-colors">
                    <i class="fa fa-grip-vertical text-gray-300 text-[10px]"></i>
                    <i class="fa fa-arrows-up-down text-[10px]"></i>
                    Space
                </div>
            </div>
        </div>
        @endif

        {{-- Validierungs-Status --}}
        <div class="border-t border-gray-200 px-4 py-2.5 text-xs">
            @if($validation['total_prods'] === 0)
                <span class="text-gray-400 italic">Keine Elemente im Projekt.</span>
            @elseif($validation['all_ok'])
                <span class="text-emerald-600 flex items-center gap-1.5">
                    <i class="fa fa-circle-check"></i> Vollständig
                </span>
            @else
                <span class="text-amber-600 flex items-center gap-1.5">
                    <i class="fa fa-triangle-exclamation"></i>
                    {{ count($validation['missing_prods']) }} nicht abgedeckt
                </span>
            @endif
        </div>

    </aside>

    {{-- ══════════════════════════════════════════════════════════════════
         RECHTS — Dokument
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 overflow-y-auto bg-slate-100 py-6 px-6">

        @if($blocks->count())

        {{-- Papier --}}
        <div class="max-w-2xl mx-auto bg-white shadow-lg px-10" id="document-blocks">

            @if(\Nwidart\Modules\Facades\Module::find('Branding')?->isEnabled())
                @include('branding::partials.document-header')
            @endif

            @foreach($blocks as $block)
            @php
                $isHeading    = $block->type === 'heading';
                $isProducts   = $block->type === 'products';
                $blockSelected = $block->config['selected'] ?? null;
                $showLabel    = $block->config['show_label'] ?? false;

                $accentColor  = 'text-amber-600';
                $focusClasses = 'focus:ring-amber-200 focus:border-amber-400 bg-amber-50';
                $blockLabel   = 'Materialliste';

                if ($isProducts) {
                    $allBlockItems = DB::table('project_product')
                        ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
                        ->where('project_product.cis_row_id_project', $projectId)
                        ->whereNull('products.deleted_at')
                        ->orderBy('project_product.sort_order')
                        ->select('products.cis_row_id', 'products.name',
                                 'project_product.product_count', 'project_product.note')
                        ->get();
                    $shownItems = $blockSelected === null
                        ? $allBlockItems
                        : $allBlockItems->filter(fn($i) => in_array($i->cis_row_id, $blockSelected));
                }
            @endphp

            <div id="block-{{ $block->cis_row_id }}"
                 wire:key="doc-{{ $block->cis_row_id }}">

                {{-- ── Space ──────────────────────────────────────────── --}}
                @if($block->type === 'space')
                @php $spaceH = (int)($block->config['height'] ?? 40); @endphp
                <div wire:key="sp-{{ $block->cis_row_id }}"
                     x-data="{ h: {{ $spaceH }}, dragging: false, startY: 0, startH: 0 }"
                     @mousedown.prevent="dragging = true; startY = $event.clientY; startH = h"
                     @mousemove.window="if(dragging) h = Math.max(10, Math.min(400, startH + ($event.clientY - startY)))"
                     @mouseup.window="if(dragging){ dragging = false; $wire.updateSpaceHeight('{{ $block->cis_row_id }}', h) }"
                     class="relative flex flex-col items-center justify-end group select-none"
                     :style="'height:' + h + 'px'">

                    {{-- Beschriftung --}}
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <span class="text-[10px] text-gray-300 tabular-nums" x-text="h + ' px'"></span>
                    </div>

                    {{-- Ziehgriff --}}
                    <div class="w-full h-3 flex items-center justify-center cursor-ns-resize opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="w-8 h-1 rounded-full bg-gray-300 group-hover:bg-gray-400 transition-colors"></div>
                    </div>
                </div>

                {{-- ── Überschrift ────────────────────────────────────── --}}
                @elseif($isHeading)
                <div class="pt-4 pb-2"
                     x-data="{ editing: false }"
                     wire:key="h-{{ $block->cis_row_id }}">
                    <div x-show="!editing" @dblclick="editing = true" class="cursor-text group">
                        <h2 class="text-2xl font-bold text-gray-900 leading-snug">
                            {{ $block->config['text'] ?? '' }}
                        </h2>
                        <div class="mt-1.5 h-px bg-gray-300"></div>
                        <p class="mt-1 text-[10px] text-gray-300 italic opacity-0 group-hover:opacity-100 transition-opacity">
                            Doppelklick zum Bearbeiten
                        </p>
                    </div>
                    <div x-show="editing" style="display:none">
                        <input type="text"
                               x-ref="hInput"
                               x-effect="editing && $nextTick(() => $refs.hInput.focus())"
                               @blur="editing = false"
                               @keydown.enter="editing = false"
                               wire:change="updateHeadingText('{{ $block->cis_row_id }}', $event.target.value)"
                               value="{{ $block->config['text'] ?? '' }}"
                               class="w-full text-2xl font-bold text-gray-900 bg-indigo-50
                                      border-0 border-b-2 border-indigo-400 focus:ring-0 px-0 py-1 rounded-none outline-none">
                        <p class="mt-2 text-[10px] text-gray-400">Enter oder Klick außerhalb speichert</p>
                    </div>
                </div>

                {{-- ── Textblock ──────────────────────────────────────── --}}
                @elseif($block->type === 'text')
                <div class="py-2"
                     x-data="{ editing: false }"
                     wire:key="txt-{{ $block->cis_row_id }}">

                    <div x-show="!editing" @dblclick="editing = true" class="cursor-text group min-h-[3rem]">
                        @if($block->config['text'] ?? '')
                            <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $block->config['text'] }}</div>
                        @else
                            <p class="text-sm text-gray-300 italic">Doppelklick zum Bearbeiten…</p>
                        @endif
                        <p class="mt-1.5 text-[10px] text-gray-300 italic opacity-0 group-hover:opacity-100 transition-opacity">
                            Doppelklick zum Bearbeiten
                        </p>
                    </div>

                    <div x-show="editing" style="display:none">
                        <textarea x-ref="txtArea"
                                  x-effect="editing && $nextTick(() => { const t=$refs.txtArea; t.focus(); t.style.height='auto'; t.style.height=t.scrollHeight+'px' })"
                                  @blur="editing = false"
                                  wire:change="updateTextBlock('{{ $block->cis_row_id }}', $event.target.value)"
                                  oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                                  rows="5"
                                  style="overflow-y:hidden"
                                  placeholder="Freitext eingeben…"
                                  class="w-full text-sm text-gray-700 leading-relaxed resize-none rounded-lg
                                         border border-violet-200 focus:ring-2 focus:ring-violet-200 focus:border-violet-400
                                         bg-violet-50 outline-none px-3 py-2.5">{{ $block->config['text'] ?? '' }}</textarea>
                        <p class="mt-1 text-[11px] text-gray-400">Klick außerhalb speichert</p>
                    </div>
                </div>

                {{-- ── Materialliste ───────────────────────────────────── --}}
                @else
                <div class="py-2">

                    {{-- Block-Steuerleiste: Label-Toggle + Item-Auswahl --}}
                    <div class="flex items-center gap-3 mb-2">

                        @if($showLabel)
                            <span class="text-[10px] font-bold uppercase tracking-[.15em] {{ $accentColor }}">{{ $blockLabel }}</span>
                            <div class="flex-1 h-px bg-gray-100"></div>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        {{-- Label ein-/ausblenden --}}
                        @if($canEdit)
                        <button type="button"
                                wire:click="toggleBlockLabel('{{ $block->cis_row_id }}')"
                                title="{{ $showLabel ? 'Abschnittsüberschrift ausblenden' : 'Abschnittsüberschrift einblenden' }}"
                                class="text-xs transition-colors {{ $showLabel ? 'text-gray-500 hover:text-gray-700' : 'text-gray-300 hover:text-gray-500' }}">
                            <i class="fa {{ $showLabel ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                        </button>
                        @endif

                        {{-- Item-Auswahl-Dropdown --}}
                        @if($canEdit && isset($allBlockItems) && $allBlockItems->count())
                        <div x-data="{ open: false, search: '' }"
                             wire:key="dd-{{ $block->cis_row_id }}"
                             class="relative">

                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-700 transition-colors">
                                @if($blockSelected === null)
                                    Alle {{ $allBlockItems->count() }}
                                @else
                                    {{ count($blockSelected) }} / {{ $allBlockItems->count() }}
                                @endif
                                <i class="fa fa-sliders text-[10px]"></i>
                            </button>

                            <div x-show="open"
                                 @click.outside="open = false; search = ''"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute right-0 top-full mt-1 z-50 w-64 bg-white border border-gray-200 rounded-xl shadow-xl"
                                 style="display:none">

                                <div class="px-3 py-2 border-b border-gray-100">
                                    <input type="text"
                                           x-model="search"
                                           x-ref="si"
                                           x-effect="open && $nextTick(() => $refs.si.focus())"
                                           @keydown.escape.stop="open = false"
                                           placeholder="Suchen…"
                                           class="w-full text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-gray-300 focus:border-gray-300 outline-none">
                                </div>

                                <div class="max-h-56 overflow-y-auto py-1">
                                    @foreach($allBlockItems as $item)
                                    @php $isChecked = $blockSelected === null || in_array($item->cis_row_id, $blockSelected ?? []); @endphp
                                    <button type="button"
                                            wire:click="toggleBlockItem('{{ $block->cis_row_id }}', '{{ $item->cis_row_id }}')"
                                            x-show="!search || '{{ addslashes(strtolower($item->name)) }}'.includes(search.toLowerCase())"
                                            class="w-full flex items-center gap-2.5 px-3 py-2.5 text-left hover:bg-gray-50 transition-colors">
                                        <span class="w-4 h-4 rounded border flex items-center justify-center shrink-0
                                                     {{ $isChecked ? 'bg-amber-500 border-amber-500' : 'border-gray-300 bg-white' }}">
                                            @if($isChecked)
                                                <i class="fa fa-check text-white" style="font-size:8px"></i>
                                            @endif
                                        </span>
                                        <span class="text-xs truncate {{ $isChecked ? 'text-gray-800 font-medium' : 'text-gray-400' }}">
                                            @if($item->product_count > 1)
                                                <span class="font-bold mr-1">{{ $item->product_count }}×</span>
                                            @endif
                                            {{ $item->name }}
                                        </span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Items --}}
                    @if(isset($shownItems) && $shownItems->count())
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left text-[10px] font-bold uppercase tracking-wider text-gray-400 py-1.5 pr-2 w-8">Pos.</th>
                                <th class="text-left text-[10px] font-bold uppercase tracking-wider text-gray-400 py-1.5 pr-3 w-14">Menge</th>
                                <th class="text-left text-[10px] font-bold uppercase tracking-wider text-gray-400 py-1.5 pr-3 w-1/4">Bezeichnung</th>
                                <th class="text-left text-[10px] font-bold uppercase tracking-wider text-gray-400 py-1.5">Beschreibung</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($shownItems as $item)
                        @php
                            $descRow  = DB::table('product_descriptions')
                                ->where('cis_row_id_product', $item->cis_row_id)
                                ->whereNull('deleted_at')->first();
                            $txt      = $descRow?->text ?? '';
                            $rowKey   = "pr-{$block->cis_row_id}-{$item->cis_row_id}";
                            $phText   = 'Ausschreibungstext für ' . $item->name . '…';
                            $excludedChildren = $block->config['excluded_children'] ?? [];
                            $children = DB::table('product_child')
                                ->join('products', 'product_child.cis_row_id_child', '=', 'products.cis_row_id')
                                ->where('product_child.cis_row_id_parent', $item->cis_row_id)
                                ->whereNull('products.deleted_at')
                                ->select('products.cis_row_id', 'products.name')
                                ->get();
                        @endphp

                        <tr wire:key="{{ $rowKey }}"
                            x-data="{ editing: false }"
                            class="group align-top border-b border-gray-100">
                            <td class="py-2.5 pr-2 text-[10px] font-mono text-gray-300 tabular-nums">{{ $loop->iteration }}</td>
                            <td class="py-2.5 pr-3 text-xs font-semibold text-gray-500 tabular-nums">{{ $item->product_count }}×</td>
                            <td class="py-2.5 pr-3">
                                <p class="text-[10px] font-bold uppercase tracking-[.12em] {{ $accentColor }}">{{ $item->name }}</p>
                                @if($item->note)
                                    <p class="mt-0.5 text-xs text-gray-400 italic">{{ $item->note }}</p>
                                @endif
                            </td>
                            <td class="py-2.5">
                                <div x-show="!editing">
                                    @if($txt)
                                        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $txt }}</p>
                                    @else
                                        <p class="text-sm text-gray-400 italic">Kein Beschreibungstext vorhanden.</p>
                                    @endif
                                    @if($canEdit)
                                    <button type="button"
                                            @click="editing = true"
                                            class="mt-1.5 text-[11px] text-gray-300 hover:text-gray-600 transition-colors
                                                   opacity-0 group-hover:opacity-100">
                                        <i class="fa fa-pencil mr-1"></i>Bearbeiten
                                    </button>
                                    @endif
                                </div>

                                <div x-show="editing" style="display:none">
                                    <textarea x-ref="ta"
                                              x-effect="editing && $nextTick(() => { const t=$refs.ta; t.focus(); t.style.height='auto'; t.style.height=t.scrollHeight+'px' })"
                                              @blur="editing = false"
                                              wire:change="updateProductDescription('{{ $item->cis_row_id }}', $event.target.value)"
                                              oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                                              rows="3"
                                              style="overflow-y:hidden"
                                              placeholder="{{ $phText }}"
                                              class="w-full text-sm text-gray-700 leading-relaxed resize-none rounded-lg
                                                     border border-gray-200 focus:ring-2 outline-none px-3 py-2.5
                                                     {{ $focusClasses }}">{{ $txt }}</textarea>
                                    <p class="mt-1 text-[11px] text-gray-400">Klick außerhalb speichert</p>
                                </div>
                            </td>
                        </tr>

                        {{-- ── Unterprodukte ─────────────────────────── --}}
                        @foreach($children as $child)
                        @php
                            $childExcluded = in_array($child->cis_row_id, $excludedChildren);
                            $childDesc = DB::table('product_descriptions')
                                ->where('cis_row_id_product', $child->cis_row_id)
                                ->whereNull('deleted_at')->first();
                            $childTxt = $childDesc?->text ?? '';
                        @endphp
                        <tr wire:key="child-{{ $block->cis_row_id }}-{{ $child->cis_row_id }}"
                            x-data="{ editing: false }"
                            class="group/child align-top border-b border-dashed border-gray-100 {{ $childExcluded ? 'opacity-40' : '' }}">
                            <td class="py-2"></td>
                            <td class="py-2"></td>
                            <td class="py-2 pr-3">
                                <div class="flex items-center gap-2 pl-3">
                                    <p class="text-[10px] font-semibold text-amber-500 uppercase tracking-wide flex-1">
                                        {{ $child->name }}
                                    </p>
                                    @if($canEdit)
                                    <button type="button"
                                            wire:click="toggleChildItem('{{ $block->cis_row_id }}', '{{ $child->cis_row_id }}')"
                                            title="{{ $childExcluded ? 'Unterprodukt einblenden' : 'Unterprodukt ausblenden' }}"
                                            class="text-[10px] transition-colors opacity-0 group-hover/child:opacity-100
                                                   {{ $childExcluded ? 'text-gray-300 hover:text-gray-500' : 'text-amber-300 hover:text-amber-500' }}">
                                        <i class="fa {{ $childExcluded ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2">
                                @if(!$childExcluded)
                                    <div x-show="!editing">
                                        @if($childTxt)
                                            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $childTxt }}</p>
                                        @else
                                            <p class="text-sm text-gray-400 italic">Kein Beschreibungstext vorhanden.</p>
                                        @endif
                                        @if($canEdit)
                                        <button type="button"
                                                @click="editing = true"
                                                class="mt-1 text-[11px] text-gray-300 hover:text-gray-600 transition-colors
                                                       opacity-0 group-hover/child:opacity-100">
                                            <i class="fa fa-pencil mr-1"></i>Bearbeiten
                                        </button>
                                        @endif
                                    </div>
                                    <div x-show="editing" style="display:none">
                                        <textarea x-ref="cta"
                                                  x-effect="editing && $nextTick(() => { const t=$refs.cta; t.focus(); t.style.height='auto'; t.style.height=t.scrollHeight+'px' })"
                                                  @blur="editing = false"
                                                  wire:change="updateProductDescription('{{ $child->cis_row_id }}', $event.target.value)"
                                                  oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                                                  rows="2"
                                                  style="overflow-y:hidden"
                                                  placeholder="Ausschreibungstext für {{ $child->name }}…"
                                                  class="w-full text-sm text-gray-700 leading-relaxed resize-none rounded-lg
                                                         border border-gray-200 focus:ring-2 focus:ring-amber-200 focus:border-amber-400
                                                         bg-amber-50 outline-none px-3 py-2">{{ $childTxt }}</textarea>
                                        <p class="mt-1 text-[11px] text-gray-400">Klick außerhalb speichert</p>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-sm text-gray-400 italic">
                        Wähle {{ $blockLabel }} über den Button oben rechts aus.
                    </p>
                    @endif

                </div>
                @endif

            </div>
            @endforeach

            @if(\Nwidart\Modules\Facades\Module::find('Branding')?->isEnabled())
                @include('branding::partials.document-footer')
            @endif

        </div>
        {{-- end Papier --}}

        {{-- Validierungs-Details --}}
        @if(!$validation['all_ok'] && $validation['total_prods'] > 0)
        <div class="max-w-2xl mx-auto mt-4 px-5 py-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700">
            <p class="font-semibold mb-3 flex items-center gap-2">
                <i class="fa fa-triangle-exclamation"></i>
                Nicht alle Produkte sind in der Ausschreibung abgedeckt:
            </p>
            <div>
                @foreach($validation['missing_prods'] as $n)
                    <p class="flex items-center gap-1.5 mb-0.5"><i class="fa fa-circle-minus text-[9px]"></i>{{ $n }}</p>
                @endforeach
            </div>
        </div>
        @endif

        @else
        <div class="max-w-2xl mx-auto bg-white shadow-lg py-24 text-center px-14">
            <i class="fa fa-file-contract text-4xl text-gray-200 block mb-4"></i>
            <p class="text-sm font-medium text-gray-400 mb-1">Noch keine Blöcke</p>
            <p class="text-xs text-gray-300">Ziehe einen Block-Typ aus dem linken Panel in die Liste.</p>
        </div>
        @endif

    </div>
    {{-- end Dokument --}}

    {{-- ══════════════════════════════════════════════════════════════════
         Rechtsklick-Kontextmenü
    ══════════════════════════════════════════════════════════════════ --}}
    <template x-teleport="body">
        <div x-show="ctx.visible"
             :style="`position:fixed;top:${ctx.y}px;left:${ctx.x}px`"
             @click.stop
             class="z-[9999] w-44 bg-white border border-gray-200 rounded-xl shadow-xl py-1"
             style="display:none">
            <button type="button"
                    @click="$wire.copyBlock(ctx.blockId); ctx.visible = false"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fa fa-copy text-xs text-gray-400 w-3"></i> Kopieren
            </button>
            <div class="border-t border-gray-100 my-1"></div>
            <button type="button"
                    @click="$wire.removeBlock(ctx.blockId); ctx.visible = false"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 transition-colors">
                <i class="fa fa-trash-can text-xs w-3"></i> Löschen
            </button>
        </div>
    </template>

</div>
