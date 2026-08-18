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
    $wareneingangEnabled = \Nwidart\Modules\Facades\Module::find('Wareneingang')?->isEnabled() ?? false;

    // ── Tab-Sichtbarkeit: einmal erreichte Stufen bleiben sichtbar (zurückschauen
    //    ist immer möglich), noch nicht erreichte Folgestufen bleiben ausgeblendet.
    $statusOrder = \App\Models\Project::STATUS_ORDER;
    $statusIndex = array_search($project->status_code, $statusOrder, true);
    $statusIndex = $statusIndex === false ? 0 : $statusIndex;
    $reached     = fn (string $code) => $statusIndex >= array_search($code, $statusOrder, true);

    $tabVisibility = [
        'beladung'      => true,
        'ausschreibung' => true,
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

            <div class="mt-3 flex items-center gap-2 flex-wrap">
                @if($nextStatusCode)
                    <form method="POST" action="{{ route('project.status.advance', $project->cis_row_id) }}"
                          onsubmit="return confirm('{{ $nextCrossesLock
                                ? 'Ausschreibung fixieren? Produkte und Ausschreibungstext können danach nur noch mit erweiterten Rechten geändert werden.'
                                : 'Status auf „' . addslashes(\App\Models\Project::STATUSES[$nextStatusCode]['label']) . '“ setzen?' }}')">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            @if($nextCrossesLock)<i class="fa fa-lock mr-1.5"></i>@else<i class="fa fa-arrow-right mr-1.5"></i>@endif
                            {{ $project->statusLabel() }} → {{ \App\Models\Project::STATUSES[$nextStatusCode]['label'] }}
                        </button>
                    </form>
                @endif

                @if($prevStatusCode && (!$prevUnlocks || $canOverrideLock))
                    <form method="POST" action="{{ route('project.status.revert', $project->cis_row_id) }}"
                          onsubmit="return confirm('Status zurück auf „{{ addslashes(\App\Models\Project::STATUSES[$prevStatusCode]['label']) }}“ setzen?{{ $prevUnlocks ? ' Die Fixierung wird dadurch aufgehoben.' : '' }}')">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">
                            <i class="fa fa-rotate-left mr-1.5"></i> Zurück
                        </button>
                    </form>
                @endif
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
</div>

{{-- Tabs --}}
<div x-data="{ tab: '{{ $initialTab }}' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 border-b border-gray-200 mb-5">
        <button type="button"
                @click="tab = 'beladung'"
                :class="tab === 'beladung' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-boxes-stacked mr-1.5"></i>
            Produkte
        </button>
        <button type="button"
                @click="tab = 'ausschreibung'"
                :class="tab === 'ausschreibung' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-file-contract mr-1.5"></i>
            Ausschreibung
        </button>
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

    {{-- Tab: Produkte --}}
    <div x-show="tab === 'beladung'" x-cloak>
        <div class="flex justify-end mb-3">
            @livewire('project.import-positions-from-project', ['projectId' => $project->cis_row_id])
        </div>
        @livewire('project.project-product-manager', ['projectId' => $project->cis_row_id])
    </div>

    {{-- Tab: Ausschreibung --}}
    <div x-show="tab === 'ausschreibung'" x-cloak>
        @livewire('project.tender-editor', ['projectId' => $project->cis_row_id])
    </div>

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
