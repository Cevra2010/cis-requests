<div class="max-w-3xl">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-800">Export-Vorlagen</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Definiere Tabellen mit eigenen Spaltennamen für den CSV-/Excel-Export von Ausschreibungen.
            Jede Vorlage steht anschließend im Export-Tab eines Projekts zur Verfügung.
        </p>
    </div>

    {{-- ── Neue Vorlage ── --}}
    <div class="cis-card mb-4">
        <div class="flex items-center gap-2">
            <input type="text" wire:model="newTemplateName" placeholder="Name der Vorlage, z. B. Standard-Tabelle"
                   class="cis-input flex-1 @error('newTemplateName') is-invalid @enderror">
            <button type="button" wire:click="createTemplate" class="btn btn-primary btn-sm shrink-0">
                <i class="fa fa-plus mr-1.5"></i> Vorlage anlegen
            </button>
        </div>
        @error('newTemplateName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- ── Vorlagenliste ── --}}
    @forelse($templates as $template)
    @php $isExpanded = $expandedTemplateId === $template->cis_row_id; @endphp
    <div class="cis-card mb-3" wire:key="tpl-{{ $template->cis_row_id }}">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                    <i class="fa fa-table text-primary-500 text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <input type="text" value="{{ $template->name }}"
                           wire:change="renameTemplate('{{ $template->cis_row_id }}', $event.target.value)"
                           class="text-sm font-medium text-gray-800 border-0 bg-transparent px-0 py-0 focus:ring-0 w-full">
                    <p class="text-xs text-gray-400">{{ $template->columns->count() }} Spalte(n)</p>
                </div>
                @if($template->is_default)
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700 shrink-0">Standard</span>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if(! $template->is_default)
                <button type="button" wire:click="setDefault('{{ $template->cis_row_id }}')" class="btn btn-ghost btn-sm">
                    Als Standard
                </button>
                @endif
                <button type="button" wire:click="toggleExpanded('{{ $template->cis_row_id }}')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-{{ $isExpanded ? 'chevron-up' : 'chevron-down' }} mr-1.5"></i>
                    Spalten
                </button>
                <button type="button" wire:click="deleteTemplate('{{ $template->cis_row_id }}')"
                        wire:confirm="Vorlage „{{ addslashes($template->name) }}“ wirklich löschen?"
                        class="text-gray-300 hover:text-red-500 transition-colors" title="Löschen">
                    <i class="fa fa-trash-can text-xs"></i>
                </button>
            </div>
        </div>

        @if($isExpanded)
        <div class="mt-4 pt-4 border-t border-gray-100">
            @if($template->columns->isEmpty())
                <p class="text-xs text-gray-400 italic mb-3">Noch keine Spalten definiert.</p>
            @else
                <div class="space-y-1.5 mb-4">
                    @foreach($template->columns as $column)
                    <div wire:key="col-{{ $column->cis_row_id }}"
                         class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 border border-gray-100">
                        <span class="text-sm font-medium text-gray-800 flex-1 truncate">{{ $column->label }}</span>
                        <span class="text-[10px] text-gray-400 bg-white border border-gray-200 rounded px-1.5 py-0.5 shrink-0">
                            {{ $fields[$column->field_key] ?? $column->field_key }}
                        </span>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" wire:click="moveColumn('{{ $column->cis_row_id }}', 'up')"
                                    class="text-gray-300 hover:text-gray-600 w-5 text-center"><i class="fa fa-arrow-up text-[10px]"></i></button>
                            <button type="button" wire:click="moveColumn('{{ $column->cis_row_id }}', 'down')"
                                    class="text-gray-300 hover:text-gray-600 w-5 text-center"><i class="fa fa-arrow-down text-[10px]"></i></button>
                            <button type="button" wire:click="removeColumn('{{ $column->cis_row_id }}')"
                                    class="text-gray-300 hover:text-red-500 w-5 text-center"><i class="fa fa-xmark text-[10px]"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-2">
                <input type="text" wire:model="newColumnLabel" placeholder="Spaltenname, z. B. Artikelbezeichnung"
                       class="cis-input flex-1 text-sm @error('newColumnLabel') is-invalid @enderror">
                <select wire:model="newColumnField" class="cis-input text-sm w-56 shrink-0 @error('newColumnField') is-invalid @enderror">
                    <option value="">– Feld wählen –</option>
                    @foreach($fields as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="addColumn('{{ $template->cis_row_id }}')" class="btn btn-ghost btn-sm shrink-0">
                    <i class="fa fa-plus mr-1"></i> Hinzufügen
                </button>
            </div>
            @error('newColumnLabel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('newColumnField')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        @endif
    </div>
    @empty
    <div class="cis-card text-center py-12 text-gray-400">
        <i class="fa fa-table text-3xl mb-3 block"></i>
        <p class="text-sm">Noch keine Export-Vorlagen angelegt.</p>
    </div>
    @endforelse
</div>
