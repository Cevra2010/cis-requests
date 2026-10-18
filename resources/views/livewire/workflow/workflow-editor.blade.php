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

    <div class="grid grid-cols-12 gap-5">
        {{-- Bausteine --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="cis-card sticky top-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Bausteine</h3>
                <div class="space-y-1.5">
                    @forelse($availableKinds as $kind => $def)
                        @php $moduleOk = \App\Services\Workflow\WorkflowStepKinds::isModuleEnabled($kind); @endphp
                        <button type="button" wire:click="addStep('{{ $kind }}')" @disabled(! $moduleOk)
                                title="{{ $moduleOk ? '' : 'Modul nicht aktiv' }}"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-left border border-dashed
                                       {{ $moduleOk ? 'border-gray-200 hover:bg-gray-50 text-gray-700' : 'border-gray-100 text-gray-300 cursor-not-allowed' }}">
                            <span class="w-2 h-2 rounded-full {{ \App\Services\Workflow\WorkflowColors::dot($def['color']) }} shrink-0"></span>
                            <span class="flex-1 truncate">{{ $def['label'] }}</span>
                            <i class="fa fa-plus text-xs text-gray-300"></i>
                        </button>
                    @empty
                        <p class="text-xs text-gray-400 italic">Alle optionalen Schritte sind bereits vergeben.</p>
                    @endforelse

                    <button type="button" wire:click="addStep('custom')"
                            class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-left border border-dashed border-gray-200 hover:bg-gray-50 text-gray-700">
                        <span class="w-2 h-2 rounded-full bg-gray-300 shrink-0"></span>
                        <span class="flex-1">Eigener Schritt</span>
                        <i class="fa fa-plus text-xs text-gray-300"></i>
                    </button>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Trigger</h3>
                    <p class="text-[11px] text-gray-400 mb-2">Auf eine Schritt-Karte ziehen – löst Maßnahmen aus, sobald das Ereignis eintritt.</p>
                    <div class="js-trigger-palette space-y-1.5" x-data="workflowTriggerPalette()" x-init="init()">
                        @forelse($eventTypes as $event)
                            <div data-event-type="{{ $event->key() }}" title="{{ $event->description() }}"
                                 class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm border border-dashed border-indigo-200 bg-white cursor-grab hover:bg-indigo-50 text-gray-700">
                                <i class="fa fa-{{ $event->icon() }} text-indigo-400 text-xs shrink-0"></i>
                                <span class="min-w-0 leading-tight break-words">{{ $event->label() }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Keine Trigger verfügbar.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Maßnahmen</h3>
                    <p class="text-[11px] text-gray-400 mb-2">Auf eine Schritt-Karte oder einen Trigger ziehen.</p>
                    <div class="js-action-palette space-y-1.5" x-data="workflowActionPalette()" x-init="init()">
                        @forelse($actionTypes as $type)
                            <div data-action-type="{{ $type->key() }}"
                                 class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm border border-dashed border-gray-200 bg-white cursor-grab hover:bg-gray-50 text-gray-700">
                                <i class="fa fa-{{ $type->icon() }} text-gray-400 text-xs shrink-0"></i>
                                <span class="min-w-0 leading-tight break-words">{{ $type->label() }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Keine Maßnahmenarten verfügbar.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Zeitleiste --}}
        <div class="col-span-12 lg:col-span-5">
            <div class="cis-card p-3" x-data="workflowStepList()" x-init="init()" wire:key="workflow-steps-{{ $projectType }}">
                <div class="js-workflow-steps space-y-2">
                    @foreach($steps as $step)
                    <div wire:key="step-{{ $step->cis_row_id }}" data-id="{{ $step->cis_row_id }}"
                         wire:click="selectStep('{{ $step->cis_row_id }}')"
                         class="group p-3 rounded-xl border-2 cursor-pointer transition-colors
                                {{ $selected?->cis_row_id === $step->cis_row_id ? 'border-primary-400 bg-primary-50/50' : 'border-gray-100 hover:bg-gray-50' }}
                                {{ ! $step->is_active ? 'opacity-50' : '' }}">
                        <div class="flex items-center gap-3">
                            @unless($step->isRequired())
                                <span class="js-drag-handle w-4 h-4 flex items-center justify-center text-gray-300 hover:text-gray-500 cursor-grab shrink-0" title="Ziehen zum Verschieben">
                                    <i class="fa fa-grip-vertical text-[11px]"></i>
                                </span>
                            @else
                                <span class="w-4 shrink-0"></span>
                            @endunless

                            <span class="w-2.5 h-2.5 rounded-full {{ \App\Services\Workflow\WorkflowColors::dot($step->color) }} shrink-0"></span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    {{ $step->label }}
                                    @if($step->isRequired())
                                        <span class="ml-1 text-[9px] uppercase tracking-wide text-gray-400">Pflicht</span>
                                    @endif
                                </p>
                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                    @if($step->hasMilestone(\App\Services\Workflow\WorkflowStepKinds::MILESTONE_LOCK))
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600"><i class="fa fa-lock mr-0.5"></i>Fixierung</span>
                                    @endif
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
                                    @if($usage > 0)
                                        <span class="text-[10px] text-gray-400">{{ $usage }} Projekt(e)</span>
                                    @endif
                                </div>
                            </div>

                            <button type="button" wire:click.stop="toggleActive('{{ $step->cis_row_id }}')"
                                    @disabled($step->isRequired())
                                    title="{{ $step->is_active ? 'Deaktivieren' : 'Aktivieren' }}"
                                    class="text-xs px-2 py-1 rounded-md {{ $step->isRequired() ? 'text-gray-200' : 'text-gray-400 hover:bg-gray-100' }} shrink-0">
                                <i class="fa {{ $step->is_active ? 'fa-toggle-on text-primary-500' : 'fa-toggle-off' }}"></i>
                            </button>
                        </div>

                        <div class="mt-2 pt-2 border-t border-gray-100">
                            <p class="text-[10px] uppercase tracking-wide text-gray-300 mb-1">Beim Erreichen</p>
                            <div class="js-action-dropzone space-y-1 min-h-[10px]"
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
                                 class="mt-2 rounded-lg border border-indigo-100 bg-indigo-50/40 p-2 {{ ! $trigger->is_active ? 'opacity-50' : '' }}">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <i class="fa fa-{{ $event?->icon() ?? 'bolt' }} text-indigo-400 text-[10px] shrink-0"></i>
                                    <span class="flex-1 min-w-0 text-[11px] font-medium text-indigo-700 truncate">
                                        {{ $event?->label() ?? 'Unbekanntes Ereignis' }}
                                    </span>
                                    <button type="button" wire:click.stop="toggleTrigger('{{ $trigger->cis_row_id }}')"
                                            title="{{ $trigger->is_active ? 'Deaktivieren' : 'Aktivieren' }}"
                                            class="text-[10px] text-gray-400 hover:bg-white rounded px-1 shrink-0">
                                        <i class="fa {{ $trigger->is_active ? 'fa-toggle-on text-primary-500' : 'fa-toggle-off' }}"></i>
                                    </button>
                                    <button type="button" wire:click.stop="deleteTrigger('{{ $trigger->cis_row_id }}')"
                                            wire:confirm="Trigger samt Maßnahmen entfernen?" class="text-gray-300 hover:text-red-500 shrink-0">
                                        <i class="fa fa-trash text-[9px]"></i>
                                    </button>
                                </div>
                                <div class="js-action-dropzone space-y-1 min-h-[10px]"
                                     data-step-id="{{ $step->cis_row_id }}" data-trigger-id="{{ $trigger->cis_row_id }}">
                                    @forelse($trigger->actions as $action)
                                        @include('livewire.workflow.partials.action-row', ['action' => $action])
                                    @empty
                                        <p class="text-[10px] text-gray-400 italic">Maßnahme hierher ziehen</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach

                        <div class="js-trigger-dropzone mt-2 min-h-[8px]" data-step-id="{{ $step->cis_row_id }}" wire:key="trigger-drop-{{ $step->cis_row_id }}">
                            @if($step->triggers->isEmpty())
                                <p class="text-[10px] text-gray-300 italic">Trigger hierher ziehen</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Detail-Panel --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="cis-card sticky top-4">
                @if(! $selected)
                    <p class="text-sm text-gray-400 italic text-center py-8">Schritt auswählen, um Details zu bearbeiten.</p>
                @else
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ $selected->label }}</h3>

                    @if(count($selectedWarnings))
                        <div class="mb-4 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-700 space-y-1">
                            @foreach($selectedWarnings as $w)
                                <p><i class="fa fa-triangle-exclamation mr-1"></i>{{ $w }}</p>
                            @endforeach
                        </div>
                    @endif

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

                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="cis-label mb-1">Tabs verbergen</p>
                        <p class="text-[11px] text-gray-400 mb-2">Angehakte Tabs sind auf diesem Schritt in der Projektansicht verborgen. Ausgegraute Tabs können hier logisch noch nicht sichtbar sein.</p>
                        <div class="space-y-1">
                            @foreach($tabs as $tabKey => $tabLabel)
                                @php
                                    $info      = $tabAvailability[$tabKey] ?? ['available' => false, 'reason' => null];
                                    $isHidden  = $info['available'] && in_array($tabKey, $selected->hiddenTabs(), true);
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

                    @unless($selected->isRequired() && $selected->kind === 'draft')
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="cis-label mb-2">Freigabe</p>
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
                    </div>
                    @endunless

                    @unless($selected->isRequired())
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="deleteStep('{{ $selected->cis_row_id }}')"
                                class="text-xs text-red-500 hover:underline">
                            <i class="fa fa-trash mr-1"></i>Schritt entfernen
                        </button>
                    </div>
                    @endunless
                @endif
            </div>
        </div>
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
