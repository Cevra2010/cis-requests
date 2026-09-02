<div>
    <div class="flex gap-6">

        {{-- Typ-Auswahl links --}}
        <div class="w-56 shrink-0">
            <div class="cis-card p-0 overflow-hidden">
                <p class="px-4 py-2.5 text-[10px] font-semibold uppercase tracking-widest text-gray-500 border-b border-gray-100">
                    Ordnungstypen
                </p>
                @foreach($types as $typeKey => $typeMeta)
                <button type="button" wire:click="setType('{{ $typeKey }}')"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm border-b border-gray-50 transition-colors text-left
                               {{ $activeType === $typeKey ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span>{{ $typeMeta['label'] }}</span>
                    @if($typeMeta['module'])
                        <span class="text-[10px] text-gray-400">{{ $typeMeta['module'] }}</span>
                    @endif
                </button>
                @endforeach
            </div>
        </div>

        {{-- Baum --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500">
                    Einträge lassen sich beliebig tief verschachteln und per Ziehen (<i class="fa fa-grip-vertical mx-0.5"></i>) neu anordnen oder verschieben.
                </p>
                @can('category.create')
                <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm shrink-0">
                    <i class="fa fa-plus mr-1.5"></i> Neuer Haupteintrag
                </button>
                @endcan
            </div>

            <div class="cis-card p-2"
                 x-data="categoryTree()"
                 x-init="init()"
                 wire:key="tree-root-{{ $activeType }}">
                <div class="js-sortable-children" data-parent-id="">
                    @forelse($tree as $node)
                        @include('livewire.category._tree-node', ['node' => $node, 'depth' => 0])
                    @empty
                        <div class="text-center py-12 text-gray-400">
                            <i class="fa fa-sitemap text-3xl mb-2 block"></i>
                            <p class="text-sm">Noch keine Einträge vom Typ „{{ CisFoundation\CisCategoryManager\CisCategoryManager::getTypeLabel($activeType) }}" angelegt.</p>
                            @can('category.create')
                            <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm mt-3">
                                Ersten Eintrag erstellen
                            </button>
                            @endcan
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── Anlegen/Bearbeiten Modal ── --}}
    @if($showFormModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-1">
                {{ $formId ? 'Eintrag bearbeiten' : ($formParentId ? 'Untereintrag anlegen' : 'Neuer Haupteintrag') }}
            </h3>
            @if($formParentId && ! $formId)
                <p class="text-xs text-gray-500 mb-4">
                    Übergeordnet: <strong>{{ \App\Models\Category::find($formParentId)?->name }}</strong>
                </p>
            @endif

            <div class="space-y-4 mt-4">
                <div>
                    <label class="cis-label" for="formName">Name</label>
                    <input type="text" id="formName" wire:model="formName" class="cis-input w-full" autofocus>
                    @error('formName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="formDescription">Beschreibung</label>
                    <textarea id="formDescription" wire:model="formDescription" class="cis-input w-full" rows="2"></textarea>
                    @error('formDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="formColor">Farbe</label>
                    <input type="color" id="formColor" wire:model="formColor"
                           class="h-9 w-16 rounded border border-gray-300 cursor-pointer">
                </div>
            </div>

            @if($formId)
                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="cis-label mb-2">Verschieben nach...</p>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="moveTargetParentId" class="cis-input flex-1 text-sm">
                            <option value="">— Oberste Ebene —</option>
                            @foreach($this->moveTargetOptions() as $optId => $optLabel)
                                <option value="{{ $optId }}">{{ $optLabel }}</option>
                            @endforeach
                        </select>
                        <select wire:model="moveAfterId" class="cis-input flex-1 text-sm">
                            <option value="">Am Anfang</option>
                            @foreach($this->moveAfterOptions() as $optId => $optLabel)
                                <option value="{{ $optId }}">Nach „{{ $optLabel }}"</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="confirmMove" class="btn btn-secondary btn-sm shrink-0">
                            Verschieben
                        </button>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end gap-2 mt-6">
                <button type="button" wire:click="cancel" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="save" class="btn btn-primary btn-sm">
                    {{ $formId ? 'Speichern' : 'Anlegen' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Lösch-Modal ── --}}
    @if($showDeleteModal && $deleteCategory)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa fa-triangle-exclamation text-red-500"></i>
                <h3 class="text-base font-semibold text-gray-900">Eintrag löschen</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                „{{ $deleteCategory->name }}" wird unwiderruflich gelöscht.
                @if($deleteDescCount > 0)
                    <strong class="text-red-600">{{ $deleteDescCount }} Untereintrag/-einträge</strong> werden dabei ebenfalls gelöscht.
                @endif
            </p>

            <label class="cis-label text-xs">
                Gib zur Bestätigung
                <span class="font-mono font-semibold text-red-600">DEL-{{ $deleteCategory->name }}</span>
                ein
            </label>
            <input type="text" wire:model="deleteConfirmText" autofocus autocomplete="off"
                   class="cis-input w-full mt-1 @error('deleteConfirmText') is-invalid @enderror"
                   placeholder="DEL-{{ $deleteCategory->name }}">
            @error('deleteConfirmText')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

            <div class="flex items-center justify-end gap-2 mt-5">
                <button type="button" wire:click="$set('showDeleteModal', false)" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="destroy"
                        class="btn btn-sm bg-red-600 text-white hover:bg-red-700 border border-red-600">
                    Endgültig löschen
                </button>
            </div>
        </div>
    </div>
    @endif

    @script
    <script>
        Alpine.data('categoryTree', () => ({
            hoverTimer: null,
            hoverEl: null,

            init() {
                this.initAll();
                new MutationObserver(() => this.initAll()).observe(this.$el, { childList: true, subtree: true });
            },

            initAll() {
                this.$el.querySelectorAll('.js-sortable-children:not([data-sortable-ready])').forEach((el) => {
                    el.dataset.sortableReady = '1';
                    Sortable.create(el, {
                        group: 'category-tree',
                        animation: 150,
                        handle: '.js-drag-handle',
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        onMove: (evt) => this.handleMove(evt),
                        onEnd: (evt) => this.handleEnd(evt),
                    });
                });
            },

            handleMove(evt) {
                const row = evt.related ? evt.related.closest('[data-id]') : null;

                if (! row) {
                    this.clearHoverTimer();
                    return true;
                }
                if (row === this.hoverEl) {
                    return true;
                }

                this.clearHoverTimer();
                this.hoverEl = row;

                const childWrap = row.querySelector(':scope > .js-sortable-children');
                const hasKids   = childWrap && childWrap.children.length > 0;
                const isClosed  = childWrap && childWrap.style.display === 'none';

                if (hasKids && isClosed) {
                    this.hoverTimer = setTimeout(() => {
                        row.dispatchEvent(new CustomEvent('force-open'));
                    }, 600);
                }

                return true;
            },

            clearHoverTimer() {
                if (this.hoverTimer) {
                    clearTimeout(this.hoverTimer);
                    this.hoverTimer = null;
                }
                this.hoverEl = null;
            },

            handleEnd(evt) {
                this.clearHoverTimer();

                const id             = parseInt(evt.item.dataset.id, 10);
                const parentIdRaw    = evt.to.dataset.parentId;
                const newParentId    = parentIdRaw ? parseInt(parentIdRaw, 10) : null;

                $wire.reorder(id, newParentId, evt.newIndex);
            },
        }));
    </script>
    @endscript
</div>
