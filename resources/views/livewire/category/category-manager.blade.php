<div>
    <div class="flex gap-6">

        {{-- Typ-Auswahl links --}}
        <div class="w-56 shrink-0">
            <div class="cis-card p-0 overflow-hidden">
                <p class="px-4 py-2.5 text-[10px] font-semibold uppercase tracking-widest text-gray-500 border-b border-gray-100">
                    Kategorie-Typen
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
                    Kategorien lassen sich beliebig tief verschachteln. Jede Kategorie kann Unterkategorien haben.
                </p>
                @can('category.create')
                <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm shrink-0">
                    <i class="fa fa-plus mr-1.5"></i> Neue Hauptkategorie
                </button>
                @endcan
            </div>

            <div class="cis-card p-2">
                @forelse($tree as $node)
                    @include('livewire.category._tree-node', ['node' => $node, 'depth' => 0])
                @empty
                    <div class="text-center py-12 text-gray-400">
                        <i class="fa fa-sitemap text-3xl mb-2 block"></i>
                        <p class="text-sm">Noch keine Kategorien vom Typ „{{ CisFoundation\CisCategoryManager\CisCategoryManager::getTypeLabel($activeType) }}" angelegt.</p>
                        @can('category.create')
                        <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm mt-3">
                            Erste Kategorie erstellen
                        </button>
                        @endcan
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Anlegen/Bearbeiten Modal ── --}}
    @if($showFormModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-1">
                {{ $formId ? 'Kategorie bearbeiten' : ($formParentId ? 'Unterkategorie anlegen' : 'Neue Hauptkategorie') }}
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
                <div class="flex items-center gap-6">
                    <div>
                        <label class="cis-label" for="formColor">Farbe</label>
                        <input type="color" id="formColor" wire:model="formColor"
                               class="h-9 w-16 rounded border border-gray-300 cursor-pointer">
                    </div>
                    <div class="flex-1">
                        <label class="cis-label" for="formSortOrder">Reihenfolge</label>
                        <input type="number" id="formSortOrder" wire:model="formSortOrder" class="cis-input w-24" min="0">
                    </div>
                </div>
            </div>

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
                <h3 class="text-base font-semibold text-gray-900">Kategorie löschen</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                „{{ $deleteCategory->name }}" wird unwiderruflich gelöscht.
                @if($deleteDescCount > 0)
                    <strong class="text-red-600">{{ $deleteDescCount }} Unterkategorie(n)</strong> werden dabei ebenfalls gelöscht.
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
</div>
