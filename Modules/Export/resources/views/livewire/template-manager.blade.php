<div class="max-w-3xl">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-800">Export-Import: Vorlagen</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Definiere Tabellen mit eigenen Spaltennamen, Filtern und Sortierung für den CSV-/Excel-Export.
            Vorlagen "vor der Ausschreibung" sind für die ausgehende Tabelle an Anbieter gedacht (inkl.
            leerer Preisfelder zum Ausfüllen); Vorlagen "nach der Ausschreibung/Auswertung" für den
            Ergebnis-Export nach Zuschlag. Jede Vorlage steht automatisch im Dokumentenmanager jedes
            Projekts zur Verfügung. Enthält eine Vorlage die Felder „Einzelpreis (Händler)" oder
            „Gesamtpreis (Händler)", kann die vom Händler ausgefüllte Datei im Angebotsvergleich des
            Projekts wieder eingelesen werden.
        </p>
    </div>

    @foreach($phases as $phaseKey => $phaseLabel)
    <div class="mb-8">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ $phaseLabel }}</h3>

        {{-- ── Neue Vorlage ── --}}
        <div class="cis-card mb-3">
            <div class="flex items-center gap-2">
                <input type="text" wire:model="newTemplateName" placeholder="Name der Vorlage, z. B. Standard-Tabelle"
                       class="cis-input flex-1 @error('newTemplateName') is-invalid @enderror">
                <button type="button" wire:click="createTemplate('{{ $phaseKey }}')" class="btn btn-primary btn-sm shrink-0">
                    <i class="fa fa-plus mr-1.5"></i> Vorlage anlegen
                </button>
            </div>
            @error('newTemplateName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- ── Vorlagenliste dieser Phase ── --}}
        @forelse($templatesByPhase->get($phaseKey, collect()) as $template)
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
                        <p class="text-xs text-gray-400">
                            {{ $template->columns->count() }} Spalte(n)
                            @if($template->filters->isNotEmpty()) &middot; {{ $template->filters->count() }} Filter @endif
                        </p>
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
                        Bearbeiten
                    </button>
                    <button type="button" wire:click="deleteTemplate('{{ $template->cis_row_id }}')"
                            wire:confirm="Vorlage „{{ addslashes($template->name) }}“ wirklich löschen?"
                            class="text-gray-300 hover:text-red-500 transition-colors" title="Löschen">
                        <i class="fa fa-trash-can text-xs"></i>
                    </button>
                </div>
            </div>

            @if($isExpanded)
            @php $fieldsForPhase = \Modules\Export\Services\ExportFieldRegistry::fieldsForPhase($template->phase); @endphp

            {{-- ── Spalten ── --}}
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Spalten</p>
                @if($template->columns->isEmpty())
                    <p class="text-xs text-gray-400 italic mb-3">Noch keine Spalten definiert.</p>
                @else
                    <div class="space-y-1.5 mb-4">
                        @foreach($template->columns as $column)
                        <div wire:key="col-{{ $column->cis_row_id }}"
                             class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 border border-gray-100">
                            <span class="text-sm font-medium text-gray-800 flex-1 truncate">{{ $column->label }}</span>
                            @if($column->isFreeField())
                                <span class="text-[10px] text-indigo-500 bg-indigo-50 border border-indigo-100 rounded px-1.5 py-0.5 shrink-0" title="{{ $column->static_value ? 'Fester Text: ' . $column->static_value : 'Leer – zum manuellen Ausfüllen nach dem Export' }}">
                                    Freifeld{{ $column->static_value ? ': „' . \Illuminate\Support\Str::limit($column->static_value, 20) . '“' : ' (leer)' }}
                                </span>
                            @else
                                <span class="text-[10px] text-gray-400 bg-white border border-gray-200 rounded px-1.5 py-0.5 shrink-0">
                                    {{ \Modules\Export\Services\ExportFieldRegistry::label($column->field_key) }}
                                </span>
                            @endif
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
                    <select wire:model.live="newColumnField" class="cis-input text-sm w-56 shrink-0 @error('newColumnField') is-invalid @enderror">
                        <option value="">– Feld wählen –</option>
                        @foreach($fieldsForPhase as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                        <option value="static_text">Freifeld (fester Text oder leer)</option>
                    </select>
                    <button type="button" wire:click="addColumn('{{ $template->cis_row_id }}')" class="btn btn-ghost btn-sm shrink-0">
                        <i class="fa fa-plus mr-1"></i> Hinzufügen
                    </button>
                </div>
                @if($newColumnField === 'static_text')
                <input type="text" wire:model="newColumnStaticValue"
                       placeholder="Fester Text für jede Zeile (leer lassen für eine leere Spalte zum manuellen Ausfüllen)…"
                       class="cis-input text-sm w-full mt-2">
                @endif
                @error('newColumnLabel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('newColumnField')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- ── Filter ── --}}
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    Filter <span class="normal-case font-normal text-gray-400">(alle Bedingungen müssen zutreffen; ohne Filter sind alle Positionen enthalten)</span>
                </p>
                @if($template->filters->isEmpty())
                    <p class="text-xs text-gray-400 italic mb-3">Keine Filter – alle Positionen sind enthalten.</p>
                @else
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($template->filters as $filter)
                        @php
                            $options = $filterOptions[$filter->field_key] ?? [];
                            $valueLabels = collect($filter->value)->map(fn ($v) => $options[$v] ?? $options[(string) $v] ?? $v)->join(', ');
                        @endphp
                        <span wire:key="filter-{{ $filter->cis_row_id }}"
                              class="inline-flex items-center gap-1.5 text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-full pl-2.5 pr-1.5 py-1">
                            {{ $filterFields[$filter->field_key] ?? $filter->field_key }}: {{ $valueLabels }}
                            <button type="button" wire:click="removeFilter('{{ $filter->cis_row_id }}')" class="text-indigo-300 hover:text-red-500">
                                <i class="fa fa-xmark text-[10px]"></i>
                            </button>
                        </span>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center gap-2 flex-wrap">
                    <select wire:model.live="newFilterField" class="cis-input text-sm w-56 shrink-0 @error('newFilterField') is-invalid @enderror">
                        <option value="">– Filter hinzufügen –</option>
                        @foreach($filterFields as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    @if($newFilterField !== '')
                        @if(($filterTypes[$newFilterField] ?? null) === 'boolean_select')
                            <select wire:model="newFilterValues.0" class="cis-input text-sm w-40 shrink-0">
                                <option value="">– wählen –</option>
                                @foreach($newFilterOptions as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="flex flex-wrap gap-1.5 max-w-md">
                                @forelse($newFilterOptions as $val => $label)
                                    <label class="inline-flex items-center gap-1.5 text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1 cursor-pointer">
                                        <input type="checkbox" wire:model="newFilterValues" value="{{ $val }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        {{ $label }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400 italic">Noch keine Optionen vorhanden.</p>
                                @endforelse
                            </div>
                        @endif
                        <button type="button" wire:click="addFilter('{{ $template->cis_row_id }}')" class="btn btn-ghost btn-sm shrink-0">
                            <i class="fa fa-plus mr-1"></i> Hinzufügen
                        </button>
                    @endif
                </div>
                @error('newFilterField')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('newFilterValues')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- ── Sortierung ── --}}
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Sortierung</p>
                <div class="flex items-center gap-2">
                    <select wire:change="setSort('{{ $template->cis_row_id }}', $event.target.value, '{{ $template->sort_direction }}')"
                            class="cis-input text-sm w-56">
                        <option value="">Keine (Standardreihenfolge)</option>
                        @foreach($sortFields as $key => $label)
                            <option value="{{ $key }}" {{ $template->sort_field === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($template->sort_field)
                        <select wire:change="setSort('{{ $template->cis_row_id }}', '{{ $template->sort_field }}', $event.target.value)"
                                class="cis-input text-sm w-40">
                            <option value="asc" {{ $template->sort_direction === 'asc' ? 'selected' : '' }}>Aufsteigend</option>
                            <option value="desc" {{ $template->sort_direction === 'desc' ? 'selected' : '' }}>Absteigend</option>
                        </select>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @empty
        <div class="cis-card text-center py-8 text-gray-400">
            <i class="fa fa-table text-2xl mb-2 block"></i>
            <p class="text-sm">Noch keine Vorlagen in dieser Phase.</p>
        </div>
        @endforelse
    </div>
    @endforeach
</div>
