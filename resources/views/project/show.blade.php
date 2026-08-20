@extends('layout.app')

@section('title', $project->name)

@section('header_actions')
    <a href="{{ route('project.edit', $project->cis_row_id) }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-pencil mr-1"></i> Bearbeiten
    </a>
    <a href="{{ route('project') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-arrow-left mr-1"></i> Projekte
    </a>
@endsection

@php
    $canOverrideLock = auth()->user()?->hasPermission(\App\Models\Project::OVERRIDE_LOCK_PERMISSION, $project->cis_row_id) ?? false;
    $nextStatusCode  = $project->nextStatusCode();
    $prevStatusCode  = $project->previousStatusCode();
    $nextCrossesLock = $nextStatusCode && ! $project->isLocked() && \App\Models\Project::codeIsLocked($nextStatusCode);
    $prevUnlocks     = $prevStatusCode && $project->isLocked() && ! \App\Models\Project::codeIsLocked($prevStatusCode);

    // ── Freigabe der Statusübergänge ──────────────────────────────────────────
    $canAdvance = $project->canAdvanceStatus(auth()->user());
    $canRevert  = $project->canRevertStatus(auth()->user()) && (! $prevUnlocks || $canOverrideLock);

    $advanceBlockedReasons = [];
    if ($nextStatusCode && ! $project->canAdvanceStatus(auth()->user())) {
        $label = \App\Models\Permission::where('slug', $project->nextTransitionPermission())->value('label');
        $advanceBlockedReasons[] = 'Erfordert die Berechtigung „' . ($label ?? $project->nextTransitionPermission()) . '“.';
    }
    $revertBlockedReasons = [];
    if ($prevStatusCode && ! $project->canRevertStatus(auth()->user())) {
        $label = \App\Models\Permission::where('slug', $project->previousTransitionPermission())->value('label');
        $revertBlockedReasons[] = 'Erfordert die Berechtigung „' . ($label ?? $project->previousTransitionPermission()) . '“.';
    }
    if ($prevStatusCode && $prevUnlocks && ! $canOverrideLock) {
        $revertBlockedReasons[] = 'Erfordert die Berechtigung, fixierte Ausschreibungen zu bearbeiten.';
    }
    $wareneingangEnabled = \Nwidart\Modules\Facades\Module::find('Wareneingang')?->isEnabled() ?? false;
    $exportEnabled = \Nwidart\Modules\Facades\Module::find('Export')?->isEnabled() ?? false;
    $exportTemplates = $exportEnabled ? \Modules\Export\Models\ExportTemplate::with('columns')->orderByDesc('is_default')->orderBy('name')->get() : collect();

    // ── Tab-Sichtbarkeit: einmal erreichte Stufen bleiben sichtbar (zurückschauen
    //    ist immer möglich), noch nicht erreichte Folgestufen bleiben ausgeblendet.
    $statusOrder = \App\Models\Project::STATUS_ORDER;
    $statusIndex = array_search($project->status_code, $statusOrder, true);
    $statusIndex = $statusIndex === false ? 0 : $statusIndex;
    $reached     = fn (string $code) => $statusIndex >= array_search($code, $statusOrder, true);

    $tabVisibility = [
        'beladung'      => true,
        'ausschreibung' => ! $project->isVehicle(),
        'export'        => true,
        'angebote'      => $reached('tender'),
        'bestellung'    => $reached('evaluated'),
        'wareneingang'  => $wareneingangEnabled && $reached('ordered'),
    ];

    // ── Tab, der beim Öffnen des Projekts automatisch aktiv ist (passend zum Status).
    $defaultTabByStatus = [
        'draft'                 => 'beladung',
        'ready_for_tender'      => 'ausschreibung',
        'tender'                => 'angebote',
        'ready_for_evaluation'  => 'angebote',
        'evaluated'             => 'bestellung',
        'ordered'               => 'wareneingang',
        'orders_in_revision'    => 'wareneingang',
        'completed'             => 'wareneingang',
    ];
    $suggestedTab = $defaultTabByStatus[$project->status_code] ?? 'beladung';
    if (empty($tabVisibility[$suggestedTab])) {
        foreach (['wareneingang', 'bestellung', 'angebote', 'ausschreibung', 'beladung'] as $fallback) {
            if ($tabVisibility[$fallback]) {
                $suggestedTab = $fallback;
                break;
            }
        }
    }
    $initialTab = request('tab', $suggestedTab);
@endphp

@section('content')

{{-- Project header --}}
<div class="cis-card mb-5">
    <div class="flex items-start justify-between gap-6">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 mb-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $project->statusBadgeClasses() }}">
                    {{ $project->statusLabel() }}
                </span>
                @if($project->tender_year)
                    <span class="text-xs text-gray-400">Ausschreibung {{ $project->tender_year }}</span>
                @endif
                @if($project->isVehicle())
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                        <i class="fa fa-truck-fast"></i> Fahrzeugausschreibung
                    </span>
                @endif
                @if($project->isLocked())
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                        <i class="fa fa-lock"></i> Fixiert
                    </span>
                @endif
            </div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $project->name }}</h1>
            @if($project->description)
                <p class="mt-1 text-sm text-gray-500">{{ $project->description }}</p>
            @endif

            <div class="mt-3 flex items-center gap-2 flex-wrap"
                 x-data="{ confirmOpen: false, pending: null }">

                @if($nextStatusCode)
                    <form method="POST" action="{{ route('project.status.advance', $project->cis_row_id) }}" x-ref="advanceForm">
                        @csrf
                    </form>
                    <div class="group relative">
                        <button type="button"
                                @if($canAdvance) @click="pending = 'advance'; confirmOpen = true" @else disabled @endif
                                class="btn btn-primary btn-sm {{ ! $canAdvance ? 'opacity-40 cursor-not-allowed' : '' }}">
                            @if($nextCrossesLock)<i class="fa fa-lock mr-1.5"></i>@else<i class="fa fa-arrow-right mr-1.5"></i>@endif
                            {{ $project->statusLabel() }} → {{ \App\Models\Project::STATUSES[$nextStatusCode]['label'] }}
                        </button>
                        @if(! $canAdvance)
                        <div class="absolute left-0 top-full mt-1.5 hidden group-hover:block z-10 w-64 px-3 py-2 rounded-lg bg-gray-900 text-white text-xs shadow-lg">
                            <i class="fa fa-lock mr-1"></i> {{ implode(' ', $advanceBlockedReasons) }}
                        </div>
                        @endif
                    </div>
                @endif

                @if($prevStatusCode)
                    <form method="POST" action="{{ route('project.status.revert', $project->cis_row_id) }}" x-ref="revertForm">
                        @csrf
                    </form>
                    <div class="group relative">
                        <button type="button"
                                @if($canRevert) @click="pending = 'revert'; confirmOpen = true" @else disabled @endif
                                class="btn btn-ghost btn-sm {{ ! $canRevert ? 'opacity-40 cursor-not-allowed' : '' }}">
                            <i class="fa fa-rotate-left mr-1.5"></i> Zurück
                        </button>
                        @if(! $canRevert)
                        <div class="absolute left-0 top-full mt-1.5 hidden group-hover:block z-10 w-64 px-3 py-2 rounded-lg bg-gray-900 text-white text-xs shadow-lg">
                            <i class="fa fa-lock mr-1"></i> {{ implode(' ', $revertBlockedReasons) }}
                        </div>
                        @endif
                    </div>
                @endif

                {{-- ── Bestätigungs-Modal ── --}}
                <div x-show="confirmOpen" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
                    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6" @click.outside="confirmOpen = false">
                        <template x-if="pending === 'advance'">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    @if($nextCrossesLock)
                                        <i class="fa fa-lock text-indigo-500"></i>
                                        <h3 class="text-base font-semibold text-gray-900">Ausschreibung fixieren?</h3>
                                    @else
                                        <i class="fa fa-arrow-right text-primary-500"></i>
                                        <h3 class="text-base font-semibold text-gray-900">Status ändern?</h3>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500">
                                    @if($nextCrossesLock)
                                        Produkte und Ausschreibungstext können danach nur noch mit erweiterten Rechten geändert werden.
                                    @else
                                        Status wird auf „{{ $nextStatusCode ? \App\Models\Project::STATUSES[$nextStatusCode]['label'] : '' }}“ gesetzt.
                                    @endif
                                </p>
                            </div>
                        </template>
                        <template x-if="pending === 'revert'">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fa fa-rotate-left text-gray-500"></i>
                                    <h3 class="text-base font-semibold text-gray-900">Status zurücksetzen?</h3>
                                </div>
                                <p class="text-sm text-gray-500">
                                    Status wird zurück auf „{{ $prevStatusCode ? \App\Models\Project::STATUSES[$prevStatusCode]['label'] : '' }}“ gesetzt.
                                    @if($prevUnlocks) Die Fixierung wird dadurch aufgehoben. @endif
                                </p>
                            </div>
                        </template>

                        <div class="flex items-center justify-end gap-2 mt-5">
                            <button type="button" @click="confirmOpen = false" class="btn btn-ghost btn-sm">Abbrechen</button>
                            <button type="button"
                                    @click="pending === 'advance' ? $refs.advanceForm.submit() : $refs.revertForm.submit()"
                                    class="btn btn-primary btn-sm">
                                Bestätigen
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if($project->isLocked())
                <p class="mt-1.5 text-xs text-gray-400">
                    Fixiert seit {{ $project->tender_locked_at?->format('d.m.Y H:i') }}
                    @if($project->lockedByUser())
                        von {{ $project->lockedByUser()->name() }}
                    @endif
                </p>
            @endif
        </div>

        <dl class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm shrink-0">
            @if($project->categoryLabel() !== '–')
            <div>
                <dt class="text-xs text-gray-400">Kategorie</dt>
                <dd class="text-gray-700 font-medium">{{ $project->categoryLabel() }}</dd>
            </div>
            @endif
            @if($project->client)
            <div>
                <dt class="text-xs text-gray-400">Auftraggeber</dt>
                <dd class="text-gray-700 font-medium">{{ $project->client }}</dd>
            </div>
            @endif
            @if($project->assigneeLabel() !== '–')
            <div>
                <dt class="text-xs text-gray-400">Verantwortlich</dt>
                <dd class="text-gray-700 font-medium">{{ $project->assigneeLabel() }}</dd>
            </div>
            @endif
            @if($project->due_date)
            <div>
                <dt class="text-xs text-gray-400">Fällig</dt>
                <dd class="text-gray-700 font-medium">{{ $project->due_date->format('d.m.Y') }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Status-Verlauf --}}
    <div class="mt-5 pt-5 border-t border-gray-100 flex items-start">
        @foreach($statusOrder as $i => $code)
            @php
                $meta      = \App\Models\Project::STATUSES[$code];
                $isCurrent = $code === $project->status_code;
                $isPast    = $statusIndex > $i;
            @endphp
            <div class="flex items-center {{ ! $loop->last ? 'flex-1' : '' }}">
                <div class="flex flex-col items-center shrink-0 w-20">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-semibold shrink-0
                                {{ $isCurrent ? 'bg-primary-600 text-white ring-4 ring-primary-100' : ($isPast ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400') }}">
                        @if($isPast)<i class="fa fa-check text-[9px]"></i>@else{{ $i + 1 }}@endif
                    </div>
                    <span class="mt-1.5 text-[10px] text-center leading-tight {{ $isCurrent ? 'text-gray-800 font-semibold' : 'text-gray-400' }}">
                        {{ $meta['label'] }}
                    </span>
                </div>
                @if(! $loop->last)
                    <div class="flex-1 h-0.5 rounded {{ $isPast ? 'bg-primary-200' : 'bg-gray-100' }}" style="margin-top: -14px;"></div>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- Tabs --}}
<div x-data="{ tab: '{{ $initialTab }}' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 border-b border-gray-200 mb-5">
        <button type="button"
                @click="tab = 'beladung'"
                :class="tab === 'beladung' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            @if($project->isVehicle())
                <i class="fa fa-truck-fast mr-1.5"></i>
                Fahrzeug-Konfiguration
            @else
                <i class="fa fa-boxes-stacked mr-1.5"></i>
                Produkte
            @endif
        </button>
        @if($tabVisibility['ausschreibung'])
        <button type="button"
                @click="tab = 'ausschreibung'"
                :class="tab === 'ausschreibung' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-file-contract mr-1.5"></i>
            Ausschreibung
        </button>
        @endif
        <button type="button"
                @click="tab = 'export'"
                :class="tab === 'export' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-file-export mr-1.5"></i>
            Export
        </button>
        @if($tabVisibility['angebote'])
        <button type="button"
                @click="tab = 'angebote'"
                :class="tab === 'angebote' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-file-invoice-dollar mr-1.5"></i>
            Angebote
        </button>
        @endif
        @if($tabVisibility['bestellung'])
        <button type="button"
                @click="tab = 'bestellung'"
                :class="tab === 'bestellung' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-cart-shopping mr-1.5"></i>
            Bestellung
        </button>
        @endif
        @if($tabVisibility['wareneingang'])
        <button type="button"
                @click="tab = 'wareneingang'"
                :class="tab === 'wareneingang' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-truck-ramp-box mr-1.5"></i>
            Wareneingang
        </button>
        @endif
    </div>

    {{-- Tab: Produkte / Fahrzeug-Konfiguration --}}
    <div x-show="tab === 'beladung'" x-cloak>
        @if($project->isVehicle())
            @livewire('project.vehicle-configuration-editor', ['projectId' => $project->cis_row_id])
        @else
            <div class="flex justify-end mb-3">
                @livewire('project.import-positions-from-project', ['projectId' => $project->cis_row_id])
            </div>
            @livewire('project.project-product-manager', ['projectId' => $project->cis_row_id])
        @endif
    </div>

    @if($tabVisibility['ausschreibung'])
    {{-- Tab: Ausschreibung --}}
    <div x-show="tab === 'ausschreibung'" x-cloak>
        @livewire('project.tender-editor', ['projectId' => $project->cis_row_id])
    </div>
    @endif

    {{-- Tab: Export --}}
    <div x-show="tab === 'export'" x-cloak>
        <div class="max-w-2xl">
            <div class="cis-card">
                <h2 class="text-base font-semibold text-gray-800 mb-1">Ausschreibung exportieren</h2>
                <p class="text-sm text-gray-500 mb-6">Exportiere die Ausschreibung in das gewünschte Format.</p>

                <div class="space-y-3">
                    {{-- PDF --}}
                    <a href="{{ route('project.export.pdf', $project->cis_row_id) }}"
                       target="_blank"
                       class="flex items-center gap-4 px-5 py-4 border border-gray-200 rounded-xl hover:border-red-200 hover:bg-red-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0 group-hover:bg-red-200 transition-colors">
                            <i class="fa fa-file-pdf text-red-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">PDF-Dokument</p>
                            <p class="text-xs text-gray-400">Druckfertige Ausschreibung als PDF</p>
                        </div>
                        <i class="fa fa-arrow-down text-gray-300 group-hover:text-red-400 transition-colors"></i>
                    </a>
                </div>
            </div>

            @if($exportEnabled)
            <div class="cis-card mt-4">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-base font-semibold text-gray-800">Tabellenexport</h2>
                    <a href="{{ route('export.templates') }}" class="text-xs text-gray-400 hover:text-primary-600 transition-colors">
                        <i class="fa fa-gear mr-1"></i>Vorlagen verwalten
                    </a>
                </div>
                <p class="text-sm text-gray-500 mb-6">Exportiere die Positionen als CSV- oder Excel-Tabelle, nach frei definierbaren Spalten.</p>

                @if($exportTemplates->isEmpty())
                <p class="text-sm text-gray-400 italic">
                    Noch keine Export-Vorlage angelegt. <a href="{{ route('export.templates') }}" class="text-primary-600 hover:underline">Jetzt anlegen</a>.
                </p>
                @else
                <div class="space-y-3">
                    @foreach($exportTemplates as $template)
                    <div class="flex items-center gap-4 px-5 py-4 border border-gray-200 rounded-xl">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                            <i class="fa fa-table text-emerald-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                {{ $template->name }}
                                @if($template->is_default)
                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700">Standard</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-400">{{ $template->columns->count() }} Spalte(n)</p>
                        </div>
                        @if($template->columns->isEmpty())
                            <span class="text-xs text-gray-300 italic shrink-0">Keine Spalten</span>
                        @else
                            <a href="{{ route('export.tender.table', [$project->cis_row_id, $template->cis_row_id, 'csv']) }}"
                               class="btn btn-ghost btn-sm shrink-0">CSV</a>
                            <a href="{{ route('export.tender.table', [$project->cis_row_id, $template->cis_row_id, 'xlsx']) }}"
                               class="btn btn-ghost btn-sm shrink-0">Excel</a>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    @if($tabVisibility['angebote'])
    {{-- Tab: Angebote --}}
    <div x-show="tab === 'angebote'" x-cloak>
        @livewire('project.offer-comparison', ['projectId' => $project->cis_row_id])
    </div>
    @endif

    @if($tabVisibility['bestellung'])
    {{-- Tab: Bestellung --}}
    <div x-show="tab === 'bestellung'" x-cloak>
        @livewire('project.award-manager', ['projectId' => $project->cis_row_id])
    </div>
    @endif

    @if($tabVisibility['wareneingang'])
    {{-- Tab: Wareneingang --}}
    <div x-show="tab === 'wareneingang'" x-cloak>
        @livewire('wareneingang.goods-receipt-manager', ['projectId' => $project->cis_row_id])
    </div>
    @endif

</div>

@endsection
