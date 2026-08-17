<div class="space-y-3">
    {{-- Eingabe --}}
    <div class="flex items-center gap-2">
        <input
            type="text"
            wire:model="newParameterText"
            wire:keydown.enter="store"
            class="cis-input flex-1 text-sm"
            placeholder="Parameter eingeben, Enter zum Hinzufügen…"
        >
        <button type="button" wire:click="store"
                class="btn btn-primary btn-sm shrink-0">
            <i class="fa fa-plus"></i>
        </button>
    </div>
    @error('parameter')
        <p class="text-xs text-red-600">{{ $message }}</p>
    @enderror

    {{-- Liste --}}
    @if($parameters->count())
    <ul class="divide-y divide-gray-100 rounded-lg border border-gray-200 overflow-hidden">
        @foreach($parameters as $parameter)
        <li class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            <span>{{ $parameter->text }}</span>
            <button type="button" wire:click="delete({{ $parameter }})"
                    class="text-gray-300 hover:text-red-500 transition-colors ml-3 shrink-0">
                <i class="fa fa-xmark"></i>
            </button>
        </li>
        @endforeach
    </ul>
    @else
    <p class="text-xs text-gray-400 italic">Noch keine Parameter vorhanden.</p>
    @endif
</div>
