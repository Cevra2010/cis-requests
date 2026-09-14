<div>
    <div class="cis-card mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Zugewiesene Lagerorte</h3>
        </div>

        @if($assignedLagerorte->isEmpty())
            <p class="text-sm text-gray-400 italic mb-3">Diesem Projekt sind noch keine Lagerorte zugewiesen.</p>
        @else
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($assignedLagerorte as $l)
                    <span wire:key="assigned-{{ $l->cis_row_id }}"
                          class="inline-flex items-center gap-1.5 text-xs bg-sky-50 text-sky-700 border border-sky-100 rounded-full pl-2.5 pr-1.5 py-1">
                        {{ $l->path() }}
                        <button type="button" wire:click="removeLagerort('{{ $l->cis_row_id }}')" class="text-sky-300 hover:text-red-500">
                            <i class="fa fa-xmark text-[10px]"></i>
                        </button>
                    </span>
                @endforeach
            </div>
        @endif

        <div class="flex items-center gap-2">
            <select wire:model="newLagerortId" class="cis-input text-sm flex-1">
                <option value="">— Lagerort auswählen —</option>
                @foreach($availableLagerorte as $l)
                    <option value="{{ $l->cis_row_id }}">{{ str_repeat('— ', $l->depth) }}{{ $l->name }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="assignLagerort" class="btn btn-ghost btn-sm shrink-0">
                <i class="fa fa-plus mr-1"></i>Zuweisen
            </button>
        </div>
    </div>

    <div class="cis-card">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Für dieses Projekt reservierter Bestand</h3>
        @if($stock->isEmpty())
            <p class="text-sm text-gray-400 italic">Aktuell kein reservierter Bestand für dieses Projekt.</p>
        @else
            <div class="space-y-1.5">
                @foreach($stock as $row)
                <div wire:key="pstock-{{ $row->cis_row_id }}" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 border border-gray-100 text-sm">
                    <div>
                        <span class="font-medium text-gray-800">{{ $row->product?->name ?? '–' }}</span>
                        <span class="text-gray-400 ml-1">{{ $row->lagerort?->path() }}</span>
                    </div>
                    <span class="text-gray-600 tabular-nums">{{ $row->quantity }}×</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
