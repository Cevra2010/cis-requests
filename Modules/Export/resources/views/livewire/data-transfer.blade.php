<div class="max-w-3xl">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-800">Import / Export</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Systemdaten als Excel-/CSV-Tabelle exportieren und geänderte Tabellen wieder einlesen. Welche Tabellen hier
            erscheinen, wird unter „Export-Import“ → Vorlage → „Anzeigen in“ festgelegt. Ein Import prüft zuerst alle Zeilen
            und schreibt erst nach „Übernehmen“.
        </p>
    </div>

    @if($resultMessage)
        <div class="mb-4 px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-700">
            <i class="fa fa-circle-check mr-1.5"></i>{{ $resultMessage }}
        </div>
    @endif

    @forelse($templatesByDataset as $datasetKey => $templates)
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ $datasets[$datasetKey] ?? $datasetKey }}</h3>

        @foreach($templates as $template)
        <div class="cis-card mb-3" wire:key="tr-{{ $template->cis_row_id }}">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                        <i class="fa fa-table text-primary-500 text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $template->name }}</p>
                        <p class="text-xs text-gray-400">{{ $template->columns->count() }} Spalte(n)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('export.transfer.download', [$template->cis_row_id, 'xlsx']) }}" class="btn btn-ghost btn-sm"><i class="fa fa-file-excel mr-1.5"></i>Excel</a>
                    <a href="{{ route('export.transfer.download', [$template->cis_row_id, 'csv']) }}" class="btn btn-ghost btn-sm"><i class="fa fa-file-csv mr-1.5"></i>CSV</a>
                    @if($template->include_import_key)
                        <button type="button" wire:click="openImport('{{ $template->cis_row_id }}')" class="btn btn-primary btn-sm">
                            <i class="fa fa-upload mr-1.5"></i>Importieren
                        </button>
                    @else
                        <span class="text-xs text-gray-400" title="In der Vorlage ist kein Import-Key aktiviert">kein Import</span>
                    @endif
                </div>
            </div>

            @if($importTemplateId === $template->cis_row_id)
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Import</p>
                    <button type="button" wire:click="closeImport" class="text-gray-300 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
                </div>

                <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-gray-600">
                @error('importFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="allowCreate" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    Zeilen ohne Import-Key als neue Datensätze anlegen
                </label>

                <button type="button" wire:click="check" wire:loading.attr="disabled" wire:target="check,importFile" class="btn btn-ghost btn-sm" @disabled(! $importFile)>
                    <i class="fa fa-magnifying-glass mr-1.5"></i>Prüfen
                </button>

                @if($plan !== null)
                    @php
                        $counts = collect($plan)->countBy('action');
                        $actionable = ($counts['create'] ?? 0) + ($counts['update'] ?? 0);
                        $labels = ['update' => 'Änderung', 'create' => 'Neu', 'unchanged' => 'Unverändert', 'skipped' => 'Übersprungen', 'error' => 'Fehler'];
                    @endphp
                    <div class="text-xs text-gray-600 flex flex-wrap gap-3">
                        @foreach($labels as $k => $label)
                            @if(($counts[$k] ?? 0) > 0)<span><span class="font-semibold">{{ $counts[$k] }}</span> {{ $label }}</span>@endif
                        @endforeach
                        @if(empty($plan))<span class="italic text-gray-400">Keine Datenzeilen gefunden.</span>@endif
                    </div>

                    <div class="max-h-72 overflow-y-auto space-y-1">
                        @foreach($plan as $entry)
                            @continue($entry['action'] === 'unchanged')
                            <div class="px-3 py-2 rounded-lg border text-xs
                                {{ $entry['action'] === 'error' ? 'bg-red-50 border-red-100 text-red-700' : ($entry['action'] === 'skipped' ? 'bg-gray-50 border-gray-100 text-gray-500' : 'bg-white border-gray-200 text-gray-700') }}">
                                <span class="font-medium">Zeile {{ $entry['line'] }} · {{ $entry['label'] }}</span>
                                <span class="ml-1 text-[10px] uppercase tracking-wide opacity-70">{{ $labels[$entry['action']] ?? $entry['action'] }}</span>
                                @if($entry['error'])<p>{{ $entry['error'] }}</p>@endif
                                @foreach($entry['changes'] as $field => [$old, $new])
                                    <p class="text-gray-500">{{ $field }}: <span class="line-through">{{ $old !== '' ? $old : '–' }}</span> → <span class="text-gray-800">{{ $new !== '' ? $new : '–' }}</span></p>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    @if($actionable > 0)
                        <button type="button" wire:click="apply" wire:confirm="{{ $actionable }} Datensatz/Datensätze jetzt schreiben?" class="btn btn-primary btn-sm">
                            <i class="fa fa-check mr-1.5"></i>Übernehmen ({{ $actionable }})
                        </button>
                    @endif
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @empty
    <div class="cis-card text-center py-10 text-gray-400">
        <i class="fa fa-table text-2xl mb-2 block"></i>
        <p class="text-sm">Noch keine Tabellen für das Import-/Export-Werkzeug freigegeben.</p>
        <p class="text-xs mt-1">Unter „Export-Import“ eine Vorlage für Systemdaten anlegen und bei „Anzeigen in“ freigeben.</p>
    </div>
    @endforelse
</div>
