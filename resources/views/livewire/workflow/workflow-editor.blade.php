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
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Maßnahmen</h3>
                    <p class="text-xs text-gray-400 italic">Folgen in Kürze (z.B. „E-Mail senden“).</p>
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
                         class="group flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors
                                {{ $selected?->cis_row_id === $step->cis_row_id ? 'border-primary-400 bg-primary-50/50' : 'border-gray-100 hover:bg-gray-50' }}
                                {{ ! $step->is_active ? 'opacity-50' : '' }}">
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
                                    <option value="{{ $tabKey }}">{{ $tabLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(\App\Services\Workflow\WorkflowStepKinds::allowsAutoAdvance($selected->kind))
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="formAutoAdvance" class="rounded border-gray-300 text-primary-600">
                            Automatisch vorrücken, sobald die Fälligkeit überschritten ist
                        </label>
                        @endif

                        <button type="button" wire:click="saveStep" class="btn btn-primary btn-sm w-full">Speichern</button>
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
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Fixierte Ausschreibungen bearbeiten</h3>
        <p class="text-xs text-gray-500 mb-3">
            Wer Produkte/Ausschreibungstext auch nach der Fixierung noch bearbeiten oder eine Fixierung durch
            Zurücksetzen aufheben darf (Projekttyp „{{ $projectTypes[$projectType] }}“).
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
            },
        }));
    </script>
    @endscript
</div>
