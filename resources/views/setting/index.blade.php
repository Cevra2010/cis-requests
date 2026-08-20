@extends('layout.app')

@section('title', 'Einstellungen')

@section('content')
<div x-data="{ tab: 'allgemein' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 border-b border-gray-200 mb-6">
        <button type="button"
                @click="tab = 'allgemein'"
                :class="tab === 'allgemein' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-sliders mr-1.5"></i>
            Allgemein
        </button>
        <button type="button"
                @click="tab = 'firma'"
                :class="tab === 'firma' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-building mr-1.5"></i>
            Firma &amp; Anschrift
        </button>
        @can('system.reset')
        <button type="button"
                @click="tab = 'lizenz'"
                :class="tab === 'lizenz' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-building-shield mr-1.5"></i>
            Lizenz
        </button>
        <button type="button"
                @click="tab = 'gefahrenzone'"
                :class="tab === 'gefahrenzone' ? 'border-b-2 border-red-600 text-red-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-triangle-exclamation mr-1.5"></i>
            Gefahrenzone
        </button>
        @endcan
    </div>

    {{-- Tab: Allgemein --}}
    <div x-show="tab === 'allgemein'" x-cloak>
        @livewire('setting.general-settings')
    </div>

    {{-- Tab: Firma & Anschrift --}}
    <div x-show="tab === 'firma'" x-cloak>
        @livewire('setting.company-settings')
    </div>

    @can('system.reset')
    {{-- Tab: Lizenz --}}
    <div x-show="tab === 'lizenz'" x-cloak>
        @livewire('setting.license-settings')
    </div>

    {{-- Tab: Gefahrenzone --}}
    <div x-show="tab === 'gefahrenzone'" x-cloak>
        @livewire('setting.danger-zone')
    </div>
    @endcan
</div>
@endsection
