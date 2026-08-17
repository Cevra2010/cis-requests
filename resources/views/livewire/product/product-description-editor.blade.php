<div class="space-y-3">
    <div class="relative">
        <textarea
            wire:model.live.debounce.800ms="newDescription"
            rows="10"
            class="cis-input w-full resize-y text-sm leading-relaxed"
            placeholder="Produktbeschreibung eingeben…"
        >{{ $newDescription }}</textarea>

        @if($descriptionDirty)
        <span class="absolute top-2 right-2 inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-700">
            <i class="fa fa-circle text-[6px]"></i> Ungespeichert
        </span>
        @endif
    </div>

    <button
        type="button"
        wire:click="store"
        @if(!$descriptionDirty) disabled @endif
        class="btn btn-primary btn-sm {{ !$descriptionDirty ? 'opacity-40 cursor-not-allowed' : '' }}"
    >
        <i class="fa fa-floppy-disk mr-1"></i> Speichern
    </button>
</div>
