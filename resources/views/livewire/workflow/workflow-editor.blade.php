<div>
    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Workflow</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Schritte, Freigeber und Maßnahmen des Projektverlaufs – getrennt je Projekttyp.
            </p>
        </div>
        <div class="flex items-center gap-1 border border-gray-200 rounded-lg p-1 bg-gray-50">
            @foreach($projectTypes as $typeKey => $typeLabel)
            <button type="button" wire:click="setType('{{ $typeKey }}')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $projectType === $typeKey ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' }}">
                {{ $typeLabel }}
            </button>
            @endforeach
        </div>
    </div>

    @if($error)
    <div class="mb-4 px-4 py-2.5 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">
        <i class="fa fa-triangle-exclamation mr-1.5"></i>{{ $error }}
    </div>
    @endif

    {{-- ── Bausteine-Leiste ─────────────────────────────────────────────── --}}
    <div class="cis-card mb-4 !py-3">
        <div class="flex flex-wrap items-start gap-x-8 gap-y-3">
            <div>
                <h3 class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Schritte hinzufügen</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($availableKinds as $kind => $def)
                        @php $moduleOk = \App\Services\Workflow\WorkflowStepKinds::isModuleEnabled($kind); @endphp
                        <button type="button" wire:click="addStep('{{ $kind }}')" @disabled(! $moduleOk)
                                title="{{ $moduleOk ? 'Schritt hinzufügen' : 'Modul nicht aktiv' }}"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs border
                                       {{ $moduleOk ? 'border-gray-200 bg-white hover:bg-gray-50 text-gray-700' : 'border-gray-100 text-gray-300 cursor-not-allowed' }}">
                            <span class="w-2 h-2 rounded-full {{ \App\Services\Workflow\WorkflowColors::dot($def['color']) }}"></span>
                            {{ $def['label'] }}<i class="fa fa-plus text-[9px] text-gray-300"></i>
                        </button>
                    @endforeach
                    <button type="button" wire:click="addStep('custom')"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs border border-dashed border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        <span class="w-2 h-2 rounded-full bg-gray-300"></span>Eigener Schritt<i class="fa fa-plus text-[9px] text-gray-300"></i>
                    </button>
                </div>
            </div>

            <div>
                <h3 class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Trigger <span class="normal-case font-normal">– auf eine Karte ziehen</span></h3>
                <div class="js-trigger-palette flex flex-wrap gap-1.5" x-data="workflowTriggerPalette()" x-init="init()">
                    @forelse($eventTypes as $event)
                        <div data-event-type="{{ $event->key() }}" title="{{ $event->description() }}"
                             class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs border border-dashed border-indigo-300 bg-indigo-50/50 cursor-grab hover:bg-indigo-50 text-indigo-700">
                            <i class="fa fa-{{ $event->icon() }} text-[10px] shrink-0"></i>{{ $event->label() }}
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Keine Trigger verfügbar.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h3 class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Maßnahmen <span class="normal-case font-normal">– auf eine Karte oder einen Trigger ziehen</span></h3>
                <div class="js-action-palette flex flex-wrap gap-1.5" x-data="workflowActionPalette()" x-init="init()">
                    @forelse(collect($actionTypes)->reject(fn ($t) => $t instanceof \App\Services\Workflow\Actions\FixationAction) as $type)
                        <div data-action-type="{{ $type->key() }}"
                             class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs border border-dashed border-gray-300 bg-white cursor-grab hover:bg-gray-50 text-gray-700">
                            <i class="fa fa-{{ $type->icon() }} text-gray-400 text-[10px] shrink-0"></i>{{ $type->label() }}
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Keine Maßnahmenarten verfügbar.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h3 class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Fixierungen <span class="normal-case font-normal">– nur auf eine Karte („Beim Erreichen“): gilt ab diesem Schritt</span></h3>
                <div class="js-action-palette flex flex-wrap gap-1.5" x-data="workflowActionPalette()" x-init="init()">
                    @foreach(collect($actionTypes)->filter(fn ($t) => $t instanceof \App\Services\Workflow\Actions\FixationAction) as $type)
                        <div data-action-type="{{ $type->key() }}" title="{{ $fixationScopes[$type->scope()]['detail'] }}"
                             class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs border border-dashed border-indigo-300 bg-indigo-50/50 cursor-grab hover:bg-indigo-50 text-indigo-700">
                            <i class="fa fa-lock text-[10px] shrink-0"></i>{{ $type->label() }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── Ablaufdiagramm ──────────────────────────────────────────────── --}}
    <div class="cis-card !p-0 overflow-hidden mb-5" x-data="workflowStepList()" x-init="init()" wire:key="workflow-steps-{{ $projectType }}">
        <div class="overflow-x-auto px-6 py-6"
             style="background-color:#f9fafb; background-image:radial-gradient(#e5e7eb 1px, transparent 1px); background-size:16px 16px;">
            <div class="flex items-start gap-8 w-max">

                {{-- Start --}}
                <div class="relative shrink-0 mt-[14px]">
                    <div class="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-sm" title="Neues Projekt">
                        <i class="fa fa-play text-sm ml-0.5"></i>
                    </div>
                    <p class="absolute left-1/2 -translate-x-1/2 top-14 text-[10px] uppercase tracking-wide text-gray-400">Start</p>
                    <div class="absolute top-1/2 -translate-y-px left-full w-8 flex items-center">
                        <div class="flex-1 border-t-2 border-gray-300"></div>
                        <i class="fa fa-chevron-right text-[10px] text-gray-400 -ml-1.5"></i>
                    </div>
                </div>

                <div class="js-workflow-steps flex items-start gap-8">
                    @foreach($steps as $step)
                    @php
                        $isSelected = $selected?->cis_row_id === $step->cis_row_id;
                        $nextStep   = $steps->slice($loop->index + 1)->first(fn ($s) => $s->is_active);
                        $dot        = \App\Services\Workflow\WorkflowColors::dot($step->color);
                    @endphp
                    <div wire:key="step-{{ $step->cis_row_id }}" data-id="{{ $step->cis_row_id }}" class="relative w-72 shrink-0">

                        {{-- Verbindung zum nächsten Schritt / zum Ende --}}
                        <div class="absolute top-7 -right-8 w-8 flex items-center pointer-events-none">
                            <div class="flex-1 border-t-2 {{ $step->is_active ? 'border-gray-300' : 'border-dashed border-gray-200' }}"></div>
                            <i class="fa fa-chevron-right text-[10px] {{ $step->is_active ? 'text-gray-400' : 'text-gray-300' }} -ml-1.5"></i>
                        </div>

                        <div wire:click="selectStep('{{ $step->cis_row_id }}')"
                             x-on:click="setTimeout(() => document.getElementById('workflow-detail')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 400)"
                             class="rounded-2xl bg-white border-2 shadow-sm cursor-pointer transition-all overflow-hidden
                                    {{ $isSelected ? 'border-primary-400 shadow-md ring-4 ring-primary-100' : 'border-gray-200 hover:border-gray-300 hover:shadow' }}
                                    {{ ! $step->is_active ? 'opacity-60' : '' }}">
                            <div class="h-1.5 {{ $dot }}"></div>

                            <div class="p-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full {{ $dot }} text-white text-xs font-semibold flex items-center justify-center shrink-0">{{ $loop->iteration }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $step->label }}</p>
                                        <div class="flex items-center gap-1 flex-wrap mt-0.5">
                                            @if($step->isRequired())<span class="text-[9px] uppercase tracking-wide text-gray-400">Pflicht</span>@endif
                                            @if($step->hasMilestone(\App\Services\Workflow\WorkflowStepKinds::MILESTONE_OFFERS))
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Angebote</span>
                                            @endif
                                            @if($step->hasMilestone(\App\Services\Workflow\WorkflowStepKinds::MILESTONE_AWARDING))
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Bestellung</span>
                                            @endif
                                            @if($step->hasMilestone(\App\Services\Workflow\WorkflowStepKinds::MILESTONE_GOODS_RECEIPT))
                                                <span class="text-[10px] px-1.5 py-0.5 rounded {{ $step->moduleEnabled() ? 'bg-gray-100 text-gray-500' : 'bg-gray-50 text-gray-300' }}">
                                                    Wareneingang{{ $step->moduleEnabled() ? '' : ' (Modul inaktiv)' }}
                                                </span>
                                            @endif
                                            @php $usage = $step->usageCount(); @endphp
                                            @if($usage > 0)<span class="text-[10px] text-gray-400"><i class="fa fa-folder-open mr-0.5"></i>{{ $usage }}</span>@endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-0.5 shrink-0">
                                        @unless($step->isRequired())
                                            <span class="js-drag-handle w-5 h-6 flex items-center justify-center text-gray-300 hover:text-gray-600 cursor-grab" title="Ziehen zum Verschieben" wire:click.stop>
                                                <i class="fa fa-grip text-xs"></i>
                                            </span>
                                        @endunless
                                        <button type="button" wire:click.stop="toggleActive('{{ $step->cis_row_id }}')"
                                                @disabled($step->isRequired())
                                                title="{{ $step->is_active ? 'Deaktivieren' : 'Aktivieren' }}"
                                                class="text-sm px-1 rounded {{ $step->isRequired() ? 'text-gray-200' : 'text-gray-400 hover:text-gray-600' }}">
                                            <i class="fa {{ $step->is_active ? 'fa-toggle-on text-primary-500' : 'fa-toggle-off' }}"></i>
                                        </button>
                                    </div>
                                </div>

                                @if(! empty($fixedByStep[$step->code] ?? []))
                                    <p class="mt-2.5 text-[11px] text-indigo-600 bg-indigo-50 rounded-lg px-2 py-1 flex items-start gap-1.5">
                                        <i class="fa fa-lock text-[10px] mt-0.5"></i>
                                        <span>Fixiert: {{ collect($fixedByStep[$step->code])->map(fn ($s) => \App\Services\Workflow\Actions\FixationAction::what($s))->join(', ') }}</span>
                                    </p>
                                @endif

                                <div class="mt-3 pt-2.5 border-t border-gray-100">
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1"><i class="fa fa-bolt mr-1"></i>Beim Erreichen</p>
                                    <div class="js-action-dropzone space-y-1 min-h-[12px]"
                                         data-step-id="{{ $step->cis_row_id }}" wire:key="actions-{{ $step->cis_row_id }}">
                                        @forelse($step->actions as $action)
                                            @include('livewire.workflow.partials.action-row', ['action' => $action])
                                        @empty
                                            <p class="text-[10px] text-gray-300 italic">Maßnahme hierher ziehen</p>
                                        @endforelse
                                    </div>
                                </div>

                                @foreach($step->triggers as $trigger)
                                    @php $event = $eventTypes[$trigger->event] ?? null; @endphp
                                    <div wire:key="trigger-{{ $trigger->cis_row_id }}"
                                         class="mt-2.5 rounded-xl border border-indigo-100 bg-indigo-50/50 p-2 {{ ! $trigger->is_active ? 'opacity-50' : '' }}">
                                        <div class="flex items-center gap-1.5 mb-1.5">
                                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-500 flex items-center justify-center shrink-0">
                                                <i class="fa fa-{{ $event?->icon() ?? 'bolt' }} text-[9px]"></i>
                                            </span>
                                            <span class="flex-1 min-w-0 text-[11px] font-medium text-indigo-700 leading-tight">{{ $event?->label() ?? 'Unbekanntes Ereignis' }}</span>
                                            <button type="button" wire:click.stop="toggleTrigger('{{ $trigger->cis_row_id }}')"
                                                    title="{{ $trigger->is_active ? 'Deaktivieren' : 'Aktivieren' }}"
                                                    class="text-xs text-gray-400 hover:bg-white rounded px-1 shrink-0">
                                                <i class="fa {{ $trigger->is_active ? 'fa-toggle-on text-primary-500' : 'fa-toggle-off' }}"></i>
                                            </button>
                                            <button type="button" wire:click.stop="deleteTrigger('{{ $trigger->cis_row_id }}')"
                                                    wire:confirm="Trigger samt Maßnahmen entfernen?" class="text-gray-300 hover:text-red-500 shrink-0">
                                                <i class="fa fa-trash text-[9px]"></i>
                                            </button>
                                        </div>
                                        <div class="js-action-dropzone space-y-1 min-h-[12px]"
                                             data-step-id="{{ $step->cis_row_id }}" data-trigger-id="{{ $trigger->cis_row_id }}">
                                            @forelse($trigger->actions as $action)
                                                @include('livewire.workflow.partials.action-row', ['action' => $action])
                                            @empty
                                                <p class="text-[10px] text-gray-400 italic">Maßnahme hierher ziehen</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach

                                <div class="js-trigger-dropzone mt-2 min-h-[10px]" data-step-id="{{ $step->cis_row_id }}" wire:key="trigger-drop-{{ $step->cis_row_id }}">
                                    @if($step->triggers->isEmpty())
                                        <p class="text-[10px] text-gray-300 italic">Trigger hierher ziehen</p>
                                    @endif
                                </div>

                                {{-- Vorschau: was beim Sprung auf den nächsten Schritt passiert --}}
                                @if($nextStep && $nextStep->actions->isNotEmpty())
                                    <div class="mt-3 pt-2.5 border-t border-dashed border-gray-200">
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">
                                            <i class="fa fa-arrow-right mr-1"></i>Beim Weiterschalten auf „{{ $nextStep->label }}“
                                        </p>
                                        <div class="space-y-1">
                                            @foreach($nextStep->actions->where('is_active', true) as $preview)
                                                @php $previewType = \App\Services\Workflow\Actions\WorkflowActionTypes::find($preview->type); @endphp
                                                <div class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                                    <i class="fa fa-{{ $previewType?->icon() ?? 'bolt' }} text-gray-300 text-[10px] w-3 text-center shrink-0"></i>
                                                    <span class="truncate">{{ $previewType?->summary($preview->config ?? []) ?? $preview->type }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Ende --}}
                <div class="relative shrink-0 mt-[14px]">
                    <div class="w-12 h-12 rounded-full bg-gray-800 text-white flex items-center justify-center shadow-sm" title="Projekt abgeschlossen">
                        <i class="fa fa-flag-checkered text-sm"></i>
                    </div>
                    <p class="absolute left-1/2 -translate-x-1/2 top-14 text-[10px] uppercase tracking-wide text-gray-400">Ende</p>
                </div>
            </div>
        </div>
        <p class="px-4 py-2 text-[11px] text-gray-400 border-t border-gray-100 bg-white">
            <i class="fa fa-hand mr-1"></i>Karten anklicken, um Details zu bearbeiten · über das Griff-Symbol verschieben · seitlich scrollen für weitere Schritte
        </p>
    </div>

    {{-- ── Details des gewählten Schritts ───────────────────────────────── --}}
    <div id="workflow-detail" class="scroll-mt-24">
    @if(! $selected)
        <div class="cis-card mb-5 text-center py-8">
            <p class="text-sm text-gray-400 italic">Schritt im Diagramm anklicken, um Details zu bearbeiten.</p>
        </div>
    @else
        <div class="mb-3 flex items-center gap-2.5">
            <span class="w-3 h-3 rounded-full {{ \App\Services\Workflow\WorkflowColors::dot($selected->color) }}"></span>
            <h2 class="text-base font-semibold text-gray-800">{{ $selected->label }}</h2>
            <span class="text-xs text-gray-400">Details & Freigabe</span>
        </div>

        @if(count($selectedWarnings))
            <div class="mb-4 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-700 space-y-1">
                @foreach($selectedWarnings as $w)
                    <p><i class="fa fa-triangle-exclamation mr-1"></i>{{ $w }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5 items-start">
            {{-- Grunddaten --}}
            <div class="cis-card">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Grunddaten</h3>
                <div class="space-y-4">
                    <div>
                        <label class="cis-label">Name</label>
                        <input type="text" wire:model="formLabel" class="cis-input w-full">
                        @error('formLabel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="cis-label">Beschreibung</label>
                        <textarea wire:model="formDescription" rows="2" class="cis-input w-full"></textarea>
                    </div>
                    <div>
                        <label class="cis-label">Farbe</label>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @foreach($colors as $c)
                            <button type="button" wire:click="$set('formColor', '{{ $c }}')"
                                    class="w-6 h-6 rounded-full {{ \App\Services\Workflow\WorkflowColors::dot($c) }} {{ $formColor === $c ? 'ring-2 ring-offset-2 ring-gray-400' : '' }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="cis-label">Standard-Tab</label>
                        <select wire:model="formDefaultTab" class="cis-input w-full">
                            <option value="">– keiner –</option>
                            @foreach($tabs as $tabKey => $tabLabel)
                                <option value="{{ $tabKey }}" @disabled(! ($tabAvailability[$tabKey]['available'] ?? false) || in_array($tabKey, $selected->hiddenTabs(), true))>{{ $tabLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" wire:click="saveStep" class="btn btn-primary btn-sm w-full">Speichern</button>
                </div>

                @unless($selected->isRequired())
                <div class="mt-5 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="deleteStep('{{ $selected->cis_row_id }}')" class="text-xs text-red-500 hover:underline">
                        <i class="fa fa-trash mr-1"></i>Schritt entfernen
                    </button>
                </div>
                @endunless
            </div>

            {{-- Freigabe --}}
            <div class="cis-card">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Freigabe</h3>
                @if($selected->isRequired() && $selected->kind === 'draft')
                    <p class="text-xs text-gray-400 italic">Der Startschritt eines Projekts braucht keine Freigabe.</p>
                @else
                    <p class="text-[11px] text-gray-400 mb-2">Wer das Projekt auf diesen Schritt weiterschalten darf.</p>
                    <div class="flex items-center gap-3 mb-3 text-sm">
                        <label class="flex items-center gap-1.5">
                            <input type="radio" wire:click="setApprovalMode('{{ $selected->cis_row_id }}', 'anyone')" @checked($selected->approval_mode === 'anyone')>
                            Alle Benutzer
                        </label>
                        <label class="flex items-center gap-1.5">
                            <input type="radio" wire:click="setApprovalMode('{{ $selected->cis_row_id }}', 'restricted')" @checked($selected->approval_mode === 'restricted')>
                            Nur folgende
                        </label>
                    </div>

                    @if($selected->approval_mode === 'restricted')
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            @forelse($selected->approvers as $approver)
                                <span wire:key="appr-{{ $approver->cis_row_id }}"
                                      class="inline-flex items-center gap-1.5 text-xs bg-gray-50 text-gray-700 border border-gray-200 rounded-full pl-2.5 pr-1.5 py-1">
                                    {{ $approver->label() }}
                                    <button type="button" wire:click="removeApprover('{{ $approver->cis_row_id }}')" class="text-gray-300 hover:text-red-500">
                                        <i class="fa fa-xmark text-[10px]"></i>
                                    </button>
                                </span>
                            @empty
                                <p class="text-xs text-gray-400 italic">Niemand kann diesen Schritt aktuell freigeben.</p>
                            @endforelse
                        </div>
                        @livewire('workflow.principal-picker', ['context' => 'transition:' . $selected->cis_row_id], key('picker-' . $selected->cis_row_id))
                    @endif
                @endif
            </div>

            {{-- Tabs verbergen --}}
            <div class="cis-card">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Tabs verbergen</h3>
                <p class="text-[11px] text-gray-400 mb-2">Angehakte Tabs sind auf diesem Schritt in der Projektansicht verborgen. Ausgegraute Tabs können hier logisch noch nicht sichtbar sein.</p>
                <div class="space-y-1">
                    @foreach($tabs as $tabKey => $tabLabel)
                        @php
                            $info     = $tabAvailability[$tabKey] ?? ['available' => false, 'reason' => null];
                            $isHidden = $info['available'] && in_array($tabKey, $selected->hiddenTabs(), true);
                        @endphp
                        <label wire:key="hide-{{ $selected->cis_row_id }}-{{ $tabKey }}-{{ $tabRevision }}"
                               title="{{ $info['available'] ? '' : $info['reason'] }}"
                               class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm
                                      {{ $info['available'] ? 'cursor-pointer hover:bg-gray-50 text-gray-700' : 'cursor-not-allowed text-gray-300 bg-gray-50/50' }}">
                            <input type="checkbox" @checked($isHidden) @disabled(! $info['available'])
                                   wire:click="toggleHiddenTab('{{ $selected->cis_row_id }}', '{{ $tabKey }}')"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:opacity-40">
                            <span class="flex-1 {{ $isHidden ? 'line-through text-gray-400' : '' }}">{{ \App\Services\Workflow\WorkflowTabs::label($tabKey, $projectType) }}</span>
                            @unless($info['available'])
                                <span class="text-[10px] italic text-gray-400 text-right max-w-[11rem] leading-tight">{{ $info['reason'] }}</span>
                            @endunless
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    </div>

    {{-- Fixierungs-Override --}}
    <div class="cis-card mt-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Fixierung aufheben</h3>
        <p class="text-xs text-gray-500 mb-3">
            Wer eine Fixierung durch Zurücksetzen des Status aufheben darf (Projekttyp „{{ $projectTypes[$projectType] }}“).
            Solange ein Projekt fixiert ist, sind Produkte und Ausschreibungstext für alle gesperrt – auch für diese Personen.
        </p>
        <div class="flex flex-wrap gap-1.5 mb-2">
            @forelse($lockOverrideApprovers as $approver)
                <span wire:key="lock-appr-{{ $approver->cis_row_id }}"
                      class="inline-flex items-center gap-1.5 text-xs bg-gray-50 text-gray-700 border border-gray-200 rounded-full pl-2.5 pr-1.5 py-1">
                    {{ $approver->label() }}
                    <button type="button" wire:click="removeApprover('{{ $approver->cis_row_id }}')" class="text-gray-300 hover:text-red-500">
                        <i class="fa fa-xmark text-[10px]"></i>
                    </button>
                </span>
            @empty
                <p class="text-xs text-gray-400 italic">Niemand kann Fixierungen aktuell aufheben.</p>
            @endforelse
        </div>
        @livewire('workflow.principal-picker', ['context' => 'lock_override'], key('picker-lock-override-' . $projectType))
    </div>

    @livewire('workflow.action-editor', key('action-editor'))

    @script
    <script>
        Alpine.data('workflowStepList', () => ({
            init() {
                this.initAll();
                new MutationObserver(() => this.initAll()).observe(this.$el, { childList: true, subtree: true });
            },
            initAll() {
                this.$el.querySelectorAll('.js-workflow-steps:not([data-sortable-ready])').forEach((el) => {
                    el.dataset.sortableReady = '1';
                    Sortable.create(el, {
                        animation: 150,
                        direction: 'horizontal',
                        handle: '.js-drag-handle',
                        onEnd: (evt) => {
                            const ids = [...el.children].map((child) => child.dataset.id);
                            $wire.reorderSteps(ids);
                        },
                    });
                });

                this.$el.querySelectorAll('.js-action-dropzone:not([data-sortable-ready])').forEach((el) => {
                    el.dataset.sortableReady = '1';
                    Sortable.create(el, {
                        // Maßnahmen lassen sich nur aus der Palette einfügen und innerhalb ihrer Liste sortieren.
                        group: { name: 'workflow-actions', pull: false, put: (to, from) => from.el.classList.contains('js-action-palette') },
                        handle: '.js-action-drag-handle',
                        animation: 150,
                        onAdd: (evt) => {
                            const type = evt.item.dataset.actionType;
                            const position = evt.newIndex;
                            evt.item.remove();
                            $wire.addActionAt(el.dataset.stepId, type, position, el.dataset.triggerId || null);
                        },
                        onEnd: (evt) => {
                            if (evt.from !== evt.to) return;
                            const ids = [...el.children].map((child) => child.dataset.id).filter(Boolean);
                            $wire.reorderActions(ids);
                        },
                    });
                });

                this.$el.querySelectorAll('.js-trigger-dropzone:not([data-sortable-ready])').forEach((el) => {
                    el.dataset.sortableReady = '1';
                    Sortable.create(el, {
                        group: { name: 'workflow-triggers', pull: false, put: (to, from) => from.el.classList.contains('js-trigger-palette') },
                        sort: false,
                        animation: 150,
                        onAdd: (evt) => {
                            const event = evt.item.dataset.eventType;
                            evt.item.remove();
                            $wire.addTriggerAt(el.dataset.stepId, event);
                        },
                    });
                });
            },
        }));

        Alpine.data('workflowTriggerPalette', () => ({
            init() {
                if (typeof Sortable === 'undefined') return;
                Sortable.create(this.$el, {
                    group: { name: 'workflow-triggers', pull: 'clone', put: false },
                    sort: false,
                    animation: 150,
                });
            },
        }));

        Alpine.data('workflowActionPalette', () => ({
            init() {
                if (typeof Sortable === 'undefined') return;
                Sortable.create(this.$el, {
                    group: { name: 'workflow-actions', pull: 'clone', put: false },
                    sort: false,
                    animation: 150,
                });
            },
        }));
    </script>
    @endscript
</div>
