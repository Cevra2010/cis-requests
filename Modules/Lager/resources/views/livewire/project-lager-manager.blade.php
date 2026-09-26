<div>
    <div class="cis-card mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Zugewiesene Lagerorte</h3>
        </div>

        @if($assignedLagerorte->isEmpty())
            <p class="text-sm text-gray-400 italic mb-3">Diesem Projekt sind noch keine Lagerorte zugewiesen.</p>
        @else
            <div class="cis-table mb-3">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Lagerort</th><th>Typ</th><th class="text-right">Aktionen</th></tr>
                    </thead>
                    <tbody>
                        @foreach($assignedLagerorte as $l)
                        <tr wire:key="assigned-{{ $l->cis_row_id }}">
                            <td class="text-sm font-mono text-gray-600">{{ $l->code() }}</td>
                            <td class="text-sm text-gray-800">{{ $l->path() }}</td>
                            <td class="text-sm text-gray-500">{{ $l->type?->name }}</td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button" wire:click="showQr('{{ $l->cis_row_id }}')" title="QR-Code ansehen" class="btn btn-ghost btn-sm">
                                    <i class="fa fa-qrcode"></i>
                                </button>
                                <button type="button" wire:click="removeLagerort('{{ $l->cis_row_id }}')" title="Zuteilung entfernen" class="btn btn-ghost btn-sm text-gray-400 hover:text-red-500">
                                    <i class="fa fa-xmark"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
        @livewire('lager.lager-uebersicht', ['projectId' => $projectId])
    </div>

    @if($qrLagerort)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" wire:click.self="closeQr">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center">
            <img src="{{ route('lager.lagerort.qr', $qrLagerort->cis_row_id) }}" alt="QR-Code" class="mx-auto w-64 h-64">
            <p class="mt-3 text-base font-semibold text-gray-900">{{ $qrLagerort->name }}</p>
            <p class="text-xs font-mono text-gray-500">{{ $qrLagerort->code() }}</p>
            <p class="text-xs text-gray-400">{{ $qrLagerort->path() }}</p>
            <div class="flex items-center justify-center gap-2 mt-5">
                <a href="{{ route('lager.lagerort.label.pdf', $qrLagerort->cis_row_id) }}" target="_blank" class="btn btn-ghost btn-sm">
                    <i class="fa fa-print mr-1.5"></i>Etikett drucken
                </a>
                <button type="button" wire:click="closeQr" class="btn btn-primary btn-sm">Schließen</button>
            </div>
        </div>
    </div>
    @endif
</div>
