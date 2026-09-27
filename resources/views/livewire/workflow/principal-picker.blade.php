<div class="relative">
    <input type="text" wire:model.live.debounce.200ms="search"
           placeholder="Benutzer, Gruppe, Rolle{{ $allowEmail ? ' oder E-Mail' : '' }} suchen…"
           class="cis-input w-full text-sm">
    @if($open && count($results))
    <div class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
        @foreach($results as $r)
        <button type="button" wire:click="select('{{ $r['type'] }}', '{{ $r['id'] }}')"
                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-gray-50">
            <span class="truncate">{{ $r['label'] }}</span>
            <span class="text-[10px] text-gray-400 shrink-0">{{ $r['sub'] }}</span>
        </button>
        @endforeach
    </div>
    @endif
</div>
