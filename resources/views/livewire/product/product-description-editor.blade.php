<div>
    @include("layout.error_success")
    <div class="cis-form-group mb-4">
        <label for="newDescription">@if($descriptionDirty) *(änderungen nicht gespeichert!) @endif</label>
        <textarea rows="14" wire:model.debounce.1s='newDescription'>{{ $newDescription}}</textarea>
    </div>
    @if($descriptionDirty == false || $newDescription == null)
        <a href="#flase" id="false" class="cis-submit bg-slate-600 cursor-default hover:bg-slate-600">Produktbeschreibung speichern</a>
    @else
        <a class="cis-submit" id="true" href="#desc" type="button" wire:click='store'>Produktbeschreibung speichern</a>
    @endif
</div>
