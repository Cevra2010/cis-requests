<div>
    @if(!$source)
        <div class="cis-form-group">
            <label for="source">Produktquelle</label>
            <input type="text" wire:model='sourceSearchString' placeholder="Suchen...">
        </div>
        <div>
        @if(!$source && $sourceSearchString)
            @foreach ($sourcesCollection as $sourceObject)
                <div class="w-full bg-slate-100 border p-3 text-slate-700 hover:bg-slate-200 cursor-pointer" wire:click='setSource("{{ $sourceObject->cis_row_id }}")'>{{ $sourceObject->name }}</div>
            @endforeach
        @endif
        </div>
    @else
        <div class="cis-form-group">
            <label for="source">Produktquelle</label>
            <input type="text" value="{{ $source->name }}">
        </div>
    @endif
</div>
