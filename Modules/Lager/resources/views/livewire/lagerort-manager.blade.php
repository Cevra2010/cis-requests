<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            Lagerorte lassen sich beliebig tief verschachteln (z.B. Lager → Raum → Regal → Fach)
            und per Ziehen (<i class="fa fa-grip-vertical mx-0.5"></i>) neu anordnen oder verschieben.
        </p>
        <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm shrink-0">
            <i class="fa fa-plus mr-1.5"></i> Neuer Haupteintrag
        </button>
    </div>

    <div class="cis-card p-2"
         x-data="lagerortTree()"
         x-init="init()"
         wire:key="lagerort-tree-root">
        <div class="js-sortable-children" data-parent-id="">
            @forelse($tree as $node)
                @include('lager::livewire._lagerort-tree-node', ['node' => $node, 'depth' => 0])
            @empty
                <div class="text-center py-12 text-gray-400">
                    <i class="fa fa-warehouse text-3xl mb-2 block"></i>
                    <p class="text-sm">Noch keine Lagerorte angelegt.</p>
                    <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm mt-3">
                        Ersten Lagerort erstellen
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Anlegen/Bearbeiten Modal ── --}}
    @if($showFormModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-1">
                {{ $formId ? 'Lagerort bearbeiten' : ($formParentId ? 'Untereintrag anlegen' : 'Neuer Haupteintrag') }}
            </h3>
            @if($formParentId && ! $formId)
                <p class="text-xs text-gray-500 mb-4">
                    Übergeordnet: <strong>{{ \Modules\Lager\Models\Lagerort::find($formParentId)?->name }}</strong>
                </p>
            @endif

            <div class="space-y-4 mt-4">
                <div>
                    <label class="cis-label" for="formName">Name</label>
                    <input type="text" id="formName" wire:model="formName" class="cis-input w-full" autofocus
                           placeholder="z.B. Lager Nord, Raum 1, Gitterbox 2, Fach 3">
                    @error('formName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
    @if($showDeleteModal && $deleteLagerort)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa fa-triangle-exclamation text-red-500"></i>
                <h3 class="text-base font-semibold text-gray-900">Lagerort löschen</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                „{{ $deleteLagerort->name }}" wird unwiderruflich gelöscht.
                @if($deleteDescCount > 0)
                    <strong class="text-red-600">{{ $deleteDescCount }} Untereintrag/-einträge</strong> werden dabei ebenfalls gelöscht.
                @endif
            </p>

            <label class="cis-label text-xs">
                Gib zur Bestätigung
                <span class="font-mono font-semibold text-red-600">DEL-{{ $deleteLagerort->name }}</span>
                ein
            </label>
            <input type="text" wire:model="deleteConfirmText" autofocus autocomplete="off"
                   class="cis-input w-full mt-1 @error('deleteConfirmText') is-invalid @enderror"
                   placeholder="DEL-{{ $deleteLagerort->name }}">
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
        Alpine.data('lagerortTree', () => ({
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
                        group: 'lagerort-tree',
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

                const id          = evt.item.dataset.id;
                const parentIdRaw = evt.to.dataset.parentId;
                const newParentId = parentIdRaw ? parentIdRaw : null;

                $wire.reorder(id, newParentId, evt.newIndex);
            },
        }));
    </script>
    @endscript
</div>
