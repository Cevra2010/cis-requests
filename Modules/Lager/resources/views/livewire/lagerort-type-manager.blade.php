<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            Typen (z.B. Halle „H“, Regal „Reg“) werden Lagerorten zugewiesen; aus Kürzel und laufender Nummer
            entsteht die Lagerort-ID (z.B. H1R2SC3-F4).
        </p>
        <button type="button" wire:click="openCreate" class="btn btn-primary btn-sm shrink-0">
            <i class="fa fa-plus mr-1.5"></i> Neuer Typ
        </button>
    </div>

    @if($error)
        <p class="mb-3 text-sm text-red-600"><i class="fa fa-triangle-exclamation mr-1"></i>{{ $error }}</p>
    @endif

    <div class="cis-table">
        <table>
            <thead>
                <tr><th>Name</th><th>Kürzel</th><th class="text-right">Lagerorte</th><th class="text-right">Aktionen</th></tr>
            </thead>
            <tbody>
                @foreach($types as $t)
                <tr wire:key="ltype-{{ $t->cis_row_id }}">
                    <td class="text-sm font-medium text-gray-800">{{ $t->name }}</td>
                    <td class="text-sm font-mono text-gray-600">{{ $t->abbreviation }}</td>
                    <td class="text-sm text-right tabular-nums">{{ $t->lagerorte_count }}</td>
                    <td class="text-right">
                        <button type="button" wire:click="openEdit('{{ $t->cis_row_id }}')" class="btn btn-ghost btn-sm"><i class="fa fa-pencil"></i></button>
                        <button type="button" wire:click="delete('{{ $t->cis_row_id }}')" class="btn btn-ghost btn-sm text-gray-400 hover:text-red-500"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ $formId ? 'Typ bearbeiten' : 'Neuer Typ' }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="cis-label">Name</label>
                    <input type="text" wire:model="formName" class="cis-input w-full" placeholder="z.B. Gitterbox" autofocus>
                    @error('formName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label">Kürzel</label>
                    <input type="text" wire:model="formAbbreviation" class="cis-input w-full" placeholder="z.B. GB">
                    @error('formAbbreviation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-6">
                <button type="button" wire:click="$set('showForm', false)" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="save" class="btn btn-primary btn-sm">Speichern</button>
            </div>
        </div>
    </div>
    @endif
</div>
