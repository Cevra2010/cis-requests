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

@section('content')

{{-- Project header --}}
<div class="cis-card mb-5">
    <div class="flex items-start justify-between gap-6">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 mb-2">
                @php $color = $project->statusColor(); @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                    {{ $color === 'green'  ? 'bg-green-100 text-green-700'   : '' }}
                    {{ $color === 'blue'   ? 'bg-blue-100 text-blue-700'     : '' }}
                    {{ $color === 'yellow' ? 'bg-yellow-100 text-yellow-700' : '' }}
                    {{ $color === 'gray'   ? 'bg-gray-100 text-gray-600'     : '' }}
                ">{{ $project->statusLabel() }}</span>
                @if($project->tender_year)
                    <span class="text-xs text-gray-400">Ausschreibung {{ $project->tender_year }}</span>
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

{{-- Tabs --}}
<div x-data="{ tab: '{{ request('tab', 'ausschreibung') }}' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 border-b border-gray-200 mb-5">
        <button type="button"
                @click="tab = 'beladung'"
                :class="tab === 'beladung' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-boxes-stacked mr-1.5"></i>
            Beladung
        </button>
        <button type="button"
                @click="tab = 'eigenschaften'"
                :class="tab === 'eigenschaften' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-list-check mr-1.5"></i>
            Eigenschaften
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
        <button type="button"
                @click="tab = 'angebote'"
                :class="tab === 'angebote' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-file-invoice-dollar mr-1.5"></i>
            Angebote
        </button>
        <button type="button"
                @click="tab = 'bestellung'"
                :class="tab === 'bestellung' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-cart-shopping mr-1.5"></i>
            Bestellung
        </button>
    </div>

    {{-- Tab: Beladung --}}
    <div x-show="tab === 'beladung'" x-cloak>
        <div class="flex justify-end mb-3">
            @livewire('project.import-positions-from-project', ['projectId' => $project->cis_row_id])
        </div>
        @livewire('project.project-product-manager', ['projectId' => $project->cis_row_id])
    </div>

    {{-- Tab: Eigenschaften --}}
    <div x-show="tab === 'eigenschaften'" x-cloak>
        <div class="cis-card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-800">Fahrzeugeigenschaften</h2>
                <a href="{{ route('property.index') }}" class="text-xs text-primary-600 hover:underline">
                    <i class="fa fa-cog mr-1"></i>Globale Eigenschaften verwalten
                </a>
            </div>
            @livewire('project.property-manager', ['projectId' => $project->cis_row_id])
        </div>
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

    {{-- Tab: Angebote --}}
    <div x-show="tab === 'angebote'" x-cloak>
        @livewire('project.offer-comparison', ['projectId' => $project->cis_row_id])
    </div>

    {{-- Tab: Bestellung --}}
    <div x-show="tab === 'bestellung'" x-cloak>
        @livewire('project.award-manager', ['projectId' => $project->cis_row_id])
    </div>

</div>

@endsection
