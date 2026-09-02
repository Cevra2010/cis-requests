<div>
    {{-- ── Hochladen ── --}}
    <form wire:submit.prevent="upload" class="cis-card mb-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Dokument hochladen</p>
        <div class="space-y-3">
            <div>
                <input type="file" wire:model="newFile" class="cis-input w-full text-sm @error('newFile') is-invalid @enderror">
                @error('newFile')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <div wire:loading wire:target="newFile" class="text-xs text-gray-400 mt-1">
                    <i class="fa fa-spinner fa-spin mr-1"></i>Wird hochgeladen…
                </div>
            </div>
            <input type="text" wire:model="newName" placeholder="Anzeigename (optional, sonst Originalname)"
                   class="cis-input w-full text-sm">
            <input type="text" wire:model="newNotes" placeholder="Notiz (optional)"
                   class="cis-input w-full text-sm">
            <button type="submit" class="btn btn-primary btn-sm" {{ $newFile ? '' : 'disabled' }}>
                <i class="fa fa-upload mr-1.5"></i>Hochladen
            </button>
        </div>
    </form>

    {{-- ── Dateiliste ── --}}
    @forelse($documents as $document)
    @php
        $ext = $document->extension();
        $icon = match(true) {
            in_array($ext, ['xlsx','xls','csv']) => 'fa-file-excel text-emerald-500',
            $ext === 'pdf' => 'fa-file-pdf text-red-500',
            in_array($ext, ['doc','docx']) => 'fa-file-word text-blue-500',
            in_array($ext, ['png','jpg','jpeg','gif','webp']) => 'fa-file-image text-violet-500',
            default => 'fa-file text-gray-400',
        };
    @endphp
    <div wire:key="doc-{{ $document->cis_row_id }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-gray-100 mb-2">
        <i class="fa {{ $icon }} text-lg w-6 text-center shrink-0"></i>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate">{{ $document->name }}</p>
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
        </div>
        <a href="{{ route('project.document.download', $document->cis_row_id) }}"
           class="text-gray-300 hover:text-primary-600 transition-colors shrink-0" title="Herunterladen">
            <i class="fa fa-download"></i>
        </a>
        <button type="button" wire:click="delete('{{ $document->cis_row_id }}')"
                wire:confirm="„{{ addslashes($document->name) }}“ endgültig löschen?"
                class="text-gray-300 hover:text-red-500 transition-colors shrink-0" title="Löschen">
            <i class="fa fa-trash-can"></i>
        </button>
    </div>
    @empty
    <p class="text-sm text-gray-400 italic text-center py-6">Noch keine Dokumente vorhanden.</p>
    @endforelse
</div>
