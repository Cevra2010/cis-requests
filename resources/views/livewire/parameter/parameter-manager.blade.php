<div>
    <div class="flex items-center justify-between mb-4 gap-4">
        <div>
            <p class="text-sm text-gray-500">
                Wiederverwendbare Text-Bausteine für die Fahrzeug-Konfiguration. Ein Parameter kann Unter-Parameter
                besitzen (z.B. „Allrad" → „Rampenwinkel") und optional einer Kategorie zugeordnet werden, um
                gleichartige Varianten auffindbar zu machen (z.B. mehrere „Wattiefe"-Parameter unter derselben Kategorie).
            </p>
        </div>
        @can('parameter.create')
        <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm shrink-0">
            <i class="fa fa-plus mr-1.5"></i> Neuer Fahrzeugparameter
        </button>
        @endcan
    </div>

    <div class="mb-4 max-w-sm">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Fahrzeugparameter durchsuchen…"
               class="cis-input w-full">
    </div>

    <div class="cis-card p-2">
        @if($all !== null)
            {{-- Suchergebnis: flach --}}
            @forelse($all as $parameter)
                <div wire:key="param-flat-{{ $parameter->cis_row_id }}"
                     class="flex items-center gap-2 py-2 px-2 rounded-lg hover:bg-gray-50 border-b border-gray-50 last:border-b-0">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-800">{{ $parameter->name }}</span>
                        @if($parameter->category)
                            <span class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ $parameter->category->name }}</span>
                        @endif
                        @if($parameter->description)
                            <p class="text-xs text-gray-400 truncate">{{ $parameter->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        @can('parameter.edit')
                        <button type="button" wire:click="openEdit('{{ $parameter->cis_row_id }}')" class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-gray-700">
                            <i class="fa fa-pencil text-xs"></i>
                        </button>
                        @endcan
                        @can('parameter.delete')
                        <button type="button" wire:click="confirmDelete('{{ $parameter->cis_row_id }}')" class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-red-500">
                            <i class="fa fa-trash text-xs"></i>
                        </button>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="text-center py-10 text-gray-400 text-sm">Keine Treffer für „{{ $search }}".</p>
            @endforelse
        @else
            {{-- Baum --}}
            @forelse($tree as $node)
                @include('livewire.parameter._parameter-node', ['node' => $node, 'depth' => 0])
            @empty
                <div class="text-center py-12 text-gray-400">
                    <i class="fa fa-list-tree text-3xl mb-2 block"></i>
                    <p class="text-sm">Noch keine Fahrzeugparameter angelegt.</p>
                    @can('parameter.create')
                    <button type="button" wire:click="openCreate()" class="btn btn-primary btn-sm mt-3">
                        Ersten Fahrzeugparameter erstellen
                    </button>
                    @endcan
                </div>
            @endforelse
        @endif
    </div>

    {{-- ── Anlegen/Bearbeiten Modal ── --}}
    @if($showFormModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-1">
                {{ $formId ? 'Fahrzeugparameter bearbeiten' : ($formParentId ? 'Unter-Parameter anlegen' : 'Neuer Fahrzeugparameter') }}
            </h3>
            @if($formParentId && ! $formId)
                <p class="text-xs text-gray-500 mb-4">
                    Übergeordneter Parameter: <strong>{{ \App\Models\TemplateParameter::find($formParentId)?->name }}</strong>
                </p>
            @endif

            <div class="space-y-4 mt-4">
                <div>
                    <label class="cis-label" for="formName">Name</label>
                    <input type="text" id="formName" wire:model="formName" class="cis-input w-full" autofocus placeholder="z.B. Allrad">
                    @error('formName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="formDescription">Beschreibungstext</label>
                    <textarea id="formDescription" wire:model="formDescription" class="cis-input w-full" rows="3"
                              placeholder="z.B. Das Fahrzeug ist ein Allrad-Fahrzeug."></textarea>
                    <p class="mt-1 text-xs text-gray-400">Dieser Text wird beim Übernehmen in eine Fahrzeug-Konfiguration eingefügt.</p>
                    @error('formDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="cis-label" for="formCategoryId">Kategorie</label>
                        <select id="formCategoryId" wire:model="formCategoryId" class="cis-input w-full">
                            <option value="">— Keine Kategorie —</option>
                            @foreach($categoryOptions as $catId => $catLabel)
                                <option value="{{ $catId }}">{{ $catLabel }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Zur Einordnung/Filterung, unabhängig von Unter-Parametern.</p>
                    </div>
                    <div>
                        <label class="cis-label" for="formSortOrder">Reihenfolge</label>
                        <input type="number" id="formSortOrder" wire:model="formSortOrder" class="cis-input w-full" min="0">
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
    @if($showDeleteModal && $deleteParameter)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa fa-triangle-exclamation text-red-500"></i>
                <h3 class="text-base font-semibold text-gray-900">Fahrzeugparameter löschen</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                „{{ $deleteParameter->name }}" wird unwiderruflich gelöscht.
                @if($deleteDescCount > 0)
                    <strong class="text-red-600">{{ $deleteDescCount }} Unter-Parameter</strong> werden dabei ebenfalls gelöscht.
                @endif
                Bereits in Fahrzeug-Konfigurationen übernommene Texte bleiben davon unberührt.
            </p>

            <label class="cis-label text-xs">
                Gib zur Bestätigung
                <span class="font-mono font-semibold text-red-600">DEL-{{ $deleteParameter->name }}</span>
                ein
            </label>
            <input type="text" wire:model="deleteConfirmText" autofocus autocomplete="off"
                   class="cis-input w-full mt-1 @error('deleteConfirmText') is-invalid @enderror"
                   placeholder="DEL-{{ $deleteParameter->name }}">
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
