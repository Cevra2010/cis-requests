@extends('layout.app')

@section('title', $project->name)

@section('header_actions')
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-document-manager'))" class="btn btn-ghost btn-sm">
        <i class="fa fa-folder-open mr-1"></i> Dokumentenmanager
    </button>
    <a href="{{ route('project.edit', $project->cis_row_id) }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-pencil mr-1"></i> Bearbeiten
    </a>
    <a href="{{ route('project') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-arrow-left mr-1"></i> Projekte
    </a>
@endsection

@php
    $wareneingangEnabled = \Nwidart\Modules\Facades\Module::find('Wareneingang')?->isEnabled() ?? false;
    $lagerEnabled        = \Nwidart\Modules\Facades\Module::find('Lager')?->isEnabled() ?? false;

    // Bündelt Reihenfolge/Sperr-/Freigabe-Logik für die Wizard-Partial (siehe unten).
    $wizard = \App\Services\Workflow\ProjectWorkflowView::for($project, auth()->user());

    // ── Tab-Sichtbarkeit: einmal erreichte Meilensteine bleiben sichtbar (zurückschauen
    //    ist immer möglich), noch nicht erreichte bleiben ausgeblendet.
    $tabVisibility = [
        'beladung'      => true,
        'ausschreibung' => ! $project->isVehicle(),
        'angebote'      => $project->hasReachedMilestone(\App\Services\Workflow\WorkflowStepKinds::MILESTONE_OFFERS),
        'bestellung'    => $project->hasReachedMilestone(\App\Services\Workflow\WorkflowStepKinds::MILESTONE_AWARDING),
        'wareneingang'  => $wareneingangEnabled && $project->hasReachedMilestone(\App\Services\Workflow\WorkflowStepKinds::MILESTONE_GOODS_RECEIPT),
        'lager'         => $lagerEnabled,
    ];

    // ── Tab, der beim Öffnen des Projekts automatisch aktiv ist (aus dem aktuellen Workflow-Schritt).
    $suggestedTab = $project->currentStep()?->default_tab ?? 'beladung';
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

{{-- ── Dokumentenmanager-Modal ── --}}
<div x-data="{ show: false }" x-on:open-document-manager.window="show = true">
    <div x-show="show" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @click.self="show = false" @keydown.escape.window="show = false" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                <h2 class="text-sm font-semibold text-gray-800"><i class="fa fa-folder-open mr-1.5 text-gray-400"></i>Dokumentenmanager</h2>
                <button type="button" @click="show = false" class="text-gray-300 hover:text-gray-600">
                    <i class="fa fa-xmark"></i>
                </button>
            </div>
            <div class="p-5 overflow-y-auto">
                @livewire('project.document-manager', ['projectId' => $project->cis_row_id])
            </div>
        </div>
    </div>
</div>

{{-- Project header --}}
<div class="cis-card mb-5">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 lg:gap-6">
        <div class="flex-1 min-w-0">
            <div class="flex items-center flex-wrap gap-x-3 gap-y-1.5 mb-2">
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

{{-- Projektverlauf (Wizard) --}}
<div class="cis-card mb-5">
    @include('project.partials.workflow-wizard', ['wizard' => $wizard, 'project' => $project])
</div>

{{-- Tabs --}}
<div x-data="{ tab: '{{ $initialTab }}' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 border-b border-gray-200 mb-5">
        @if($tabVisibility['lager'])
        <button type="button"
                @click="tab = 'lager'"
                :class="tab === 'lager' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-warehouse mr-1.5"></i>
            Lager
        </button>
        @endif
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

    @if($tabVisibility['lager'])
    {{-- Tab: Lager --}}
    <div x-show="tab === 'lager'" x-cloak>
        @livewire('lager.project-lager-manager', ['projectId' => $project->cis_row_id])
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
