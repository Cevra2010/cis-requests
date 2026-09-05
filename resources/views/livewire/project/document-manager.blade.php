<div>
    {{-- ── Hochladen ── --}}
    <form wire:submit.prevent="submitUpload" class="cis-card mb-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Dokument hochladen</p>

        <div x-data="{ isDragging: false }"
             x-on:dragover.prevent="isDragging = true"
             x-on:dragenter.prevent="isDragging = true"
             x-on:dragleave.prevent="isDragging = false"
             x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
             @click="$refs.fileInput.click()"
             :class="isDragging ? 'border-primary-400 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
             class="border-2 border-dashed rounded-xl px-4 py-6 text-center cursor-pointer transition-colors mb-3">
            <input type="file" x-ref="fileInput" wire:model="newFiles" multiple class="hidden">
            <i class="fa fa-cloud-arrow-up text-2xl text-gray-300 mb-2 block"></i>
            <p class="text-sm text-gray-500">Datei hier reinziehen oder klicken zum Auswählen</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Mehrere Dateien möglich, je max. 20 MB</p>

            @if($newFiles)
                <div class="mt-3 flex flex-wrap justify-center gap-1.5" @click.stop>
                    @foreach($newFiles as $file)
                        <span class="text-xs px-2 py-1 rounded-full bg-white border border-gray-200 text-gray-600">
                            <i class="fa fa-file mr-1 text-gray-400"></i>{{ $file->getClientOriginalName() }}
                        </span>
                    @endforeach
                </div>
            @endif

            <div wire:loading wire:target="newFiles" class="text-xs text-gray-400 mt-2">
                <i class="fa fa-spinner fa-spin mr-1"></i>Wird hochgeladen…
            </div>
        </div>
        @error('newFiles')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
        @error('newFiles.*')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror

        <div class="grid grid-cols-2 gap-2 mb-3" @click.stop>
            <input type="text" wire:model="newName"
                   placeholder="{{ count($newFiles) > 1 ? 'Anzeigename (nur bei 1 Datei)' : 'Anzeigename (optional)' }}"
                   {{ count($newFiles) > 1 ? 'disabled' : '' }}
                   class="cis-input text-sm disabled:bg-gray-50 disabled:text-gray-300">
            <input type="text" wire:model="newNotes" placeholder="Notiz (optional)" class="cis-input text-sm">
        </div>
        <button type="submit" class="btn btn-primary btn-sm" {{ $newFiles ? '' : 'disabled' }}>
            <i class="fa fa-upload mr-1.5"></i>{{ count($newFiles) > 1 ? count($newFiles) . ' Dateien hochladen' : 'Hochladen' }}
        </button>
    </form>

    {{-- ── Verzeichnisse + Dateien ── --}}
    @php
        $tables  = \App\Http\Livewire\Project\DocumentManager::FOLDER_TABLES;
        $pdf     = \App\Http\Livewire\Project\DocumentManager::FOLDER_PDF;
        $uploads = \App\Http\Livewire\Project\DocumentManager::FOLDER_UPLOADS;
        $folders = [
            ['key' => 'all', 'label' => 'Alle', 'icon' => 'fa-folder-open', 'count' => array_sum($counts)],
            ['key' => $tables, 'label' => 'Tabellen', 'icon' => 'fa-file-excel', 'count' => $counts[$tables]],
            ['key' => $pdf, 'label' => 'PDF-Dateien', 'icon' => 'fa-file-pdf', 'count' => $counts[$pdf]],
            ['key' => $uploads, 'label' => 'Uploads', 'icon' => 'fa-folder', 'count' => $counts[$uploads]],
        ];
    @endphp
    <div class="flex gap-4" style="min-height: 320px">
        {{-- Verzeichnisse --}}
        <div class="w-40 shrink-0 space-y-0.5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5 px-2">Verzeichnisse</p>

            @foreach($folders as $folder)
                <button type="button" wire:click="setFolder('{{ $folder['key'] }}')"
                        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm text-left transition-colors
                               {{ $activeFolder === $folder['key'] ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i class="fa {{ $folder['icon'] }} w-4 text-center shrink-0 {{ $activeFolder === $folder['key'] ? '' : 'text-gray-400' }}"></i>
                    <span class="flex-1 truncate">{{ $folder['label'] }}</span>
                    <span class="text-[10px] text-gray-400">{{ $folder['count'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- Dateien --}}
        <div class="flex-1 min-w-0">
            @forelse($documents as $document)
            @php
                $icon = match($document->folder) {
                    $tables => 'fa-file-excel text-emerald-500',
                    $pdf => 'fa-file-pdf text-red-500',
                    default => match($document->extension()) {
                        'doc', 'docx' => 'fa-file-word text-blue-500',
                        'png', 'jpg', 'jpeg', 'gif', 'webp' => 'fa-file-image text-violet-500',
                        default => 'fa-file text-gray-400',
                    },
                };
            @endphp
            <div wire:key="doc-{{ $document->exists ? $document->cis_row_id : md5($document->downloadUrl) }}"
                 class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-gray-100 mb-2">
                <i class="fa {{ $icon }} text-lg w-6 text-center shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $document->name }}</p>
                    @if($document->exists)
                    <p class="text-[11px] text-gray-400">
                        {{ $document->sizeForHumans() }} · {{ $document->created_at->format('d.m.Y H:i') }}
                        @if($document->uploadedBy) · {{ $document->uploadedBy->firstname }} {{ $document->uploadedBy->lastname }} @endif
                        @if($document->linked_type === \App\Models\ProjectDocument::LINK_OFFER_IMPORT)
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600">Angebotsimport</span>
                        @endif
                    </p>
                    @if($document->notes)
                        <p class="text-xs text-gray-400 italic mt-0.5">{{ $document->notes }}</p>
                    @endif
                    @else
                    <p class="text-[11px] text-gray-400">
                        <span class="px-1.5 py-0.5 rounded bg-gray-50 text-gray-500">
                            <i class="fa fa-bolt mr-0.5"></i>Wird bei Bedarf automatisch erzeugt
                        </span>
                    </p>
                    @endif
                </div>
                <a href="{{ $document->exists ? route('project.document.download', $document->cis_row_id) : $document->downloadUrl }}"
                   class="text-gray-300 hover:text-primary-600 transition-colors shrink-0" title="Herunterladen"
                   @if(! $document->exists) target="_blank" @endif>
                    <i class="fa fa-download"></i>
                </a>
                @if($document->exists)
                <button type="button" wire:click="delete('{{ $document->cis_row_id }}')"
                        wire:confirm="„{{ addslashes($document->name) }}“ endgültig löschen?"
                        class="text-gray-300 hover:text-red-500 transition-colors shrink-0" title="Löschen">
                    <i class="fa fa-trash-can"></i>
                </button>
                @endif
            </div>
            @empty
            <div class="flex flex-col items-center justify-center h-full py-12 text-gray-300">
                <i class="fa fa-folder-open text-3xl mb-2"></i>
                <p class="text-sm text-gray-400">Keine Dokumente in diesem Verzeichnis.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
