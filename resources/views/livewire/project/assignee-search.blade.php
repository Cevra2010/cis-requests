<div class="relative">
    {{-- Hidden inputs carry the selected value into the parent form --}}
    <input type="hidden" name="assignee_type" value="{{ $selectedType }}">
    <input type="hidden" name="assignee_id"   value="{{ $selectedId }}">

    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.200ms="search"
            wire:focus="$set('open', true)"
            class="cis-input w-full pr-8"
            placeholder="Benutzer oder Gruppe suchen…"
            autocomplete="off"
        >
        @if($selectedId)
            <button type="button" wire:click="clear"
                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-700">
                <i class="fa fa-times text-xs"></i>
            </button>
        @endif
    </div>

    @if($open && count($results))
    <div class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg max-h-64 overflow-y-auto">
        @foreach($results as $result)
        <button type="button"
                wire:click="select('{{ $result['id'] }}', '{{ $result['type'] }}', '{{ addslashes($result['label']) }}')"
                class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-gray-50 transition-colors">
            <span class="flex-shrink-0 w-5 text-center text-gray-400">
                @if($result['type'] === 'user')
                    <i class="fa fa-user text-xs"></i>
                @else
                    <i class="fa fa-people-group text-xs"></i>
                @endif
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-sm font-medium text-gray-900 truncate">{{ $result['label'] }}</span>
            </span>
            <span class="text-[10px] text-gray-400 shrink-0">{{ $result['sub'] }}</span>
        </button>
        @endforeach
    </div>
    @endif

    @if($selectedId)
    <p class="mt-1 text-xs text-gray-500">
        <i class="fa {{ $selectedType === 'user' ? 'fa-user' : 'fa-people-group' }} mr-1"></i>
        {{ $selectedName }}
        <span class="text-gray-400">({{ $selectedType === 'user' ? 'Benutzer' : 'Gruppe' }})</span>
    </p>
    @endif
</div>
