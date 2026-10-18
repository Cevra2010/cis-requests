@extends('layout.app')

@section('title', 'Export-Import')

@section('content')
<div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') === 'transfer' ? 'transfer' : 'templates' }">
    <div class="flex gap-1 border-b border-gray-200 mb-6">
        <button type="button" @click="tab = 'templates'"
                :class="tab === 'templates' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-table mr-1.5"></i>Vorlagen
        </button>
        @can('export.transfer')
        <button type="button" @click="tab = 'transfer'"
                :class="tab === 'transfer' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="px-4 py-2.5 text-sm font-medium transition-colors rounded-t-lg -mb-px">
            <i class="fa fa-right-left mr-1.5"></i>Import / Export
        </button>
        @endcan
    </div>

    <div x-show="tab === 'templates'" x-cloak>
        @livewire('export.template-manager')
    </div>

    @can('export.transfer')
    <div x-show="tab === 'transfer'" x-cloak>
        @livewire('export.data-transfer')
    </div>
    @endcan
</div>
@endsection
