@extends('layout.app')

@section('title', 'Dashboard')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    {{-- Stat Card: Projekte --}}
    <div class="cis-card p-5 flex items-center gap-4">
        <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-blue-50 shrink-0">
            <i class="fa fa-folder text-blue-600 text-lg"></i>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Projekte</p>
            <p class="text-2xl font-semibold text-gray-900 mt-0.5">{{ \App\Models\Project::count() }}</p>
        </div>
    </div>

    {{-- Stat Card: Produkte --}}
    <div class="cis-card p-5 flex items-center gap-4">
        <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-green-50 shrink-0">
            <i class="fa fa-box-archive text-green-600 text-lg"></i>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Produktdatensätze</p>
            <p class="text-2xl font-semibold text-gray-900 mt-0.5">{{ \App\Models\Product::count() }}</p>
        </div>
    </div>

    {{-- Stat Card: Quellen --}}
    <div class="cis-card p-5 flex items-center gap-4">
        <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-purple-50 shrink-0">
            <i class="fa fa-truck text-purple-600 text-lg"></i>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Produktquellen</p>
            <p class="text-2xl font-semibold text-gray-900 mt-0.5">{{ \App\Models\ProductSource::count() }}</p>
        </div>
    </div>

</div>

<div class="cis-card">
    <div class="cis-card-header">
        <h2 class="cis-card-title">Schnellzugriff</h2>
    </div>
    <div class="cis-card-body">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('project.create') }}"
               class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors text-center group">
                <i class="fa fa-folder-plus text-xl text-gray-400 group-hover:text-primary-600 transition-colors"></i>
                <span class="text-xs font-medium text-gray-600 group-hover:text-primary-700">Neues Projekt</span>
            </a>
            <a href="{{ route('product.create') }}"
               class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors text-center group">
                <i class="fa fa-circle-plus text-xl text-gray-400 group-hover:text-primary-600 transition-colors"></i>
                <span class="text-xs font-medium text-gray-600 group-hover:text-primary-700">Neues Produkt</span>
            </a>
            <a href="{{ route('source.create') }}"
               class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors text-center group">
                <i class="fa fa-truck-fast text-xl text-gray-400 group-hover:text-primary-600 transition-colors"></i>
                <span class="text-xs font-medium text-gray-600 group-hover:text-primary-700">Neue Quelle</span>
            </a>
            <a href="{{ route('price') }}"
               class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors text-center group">
                <i class="fa fa-tags text-xl text-gray-400 group-hover:text-primary-600 transition-colors"></i>
                <span class="text-xs font-medium text-gray-600 group-hover:text-primary-700">Preispflege</span>
            </a>
        </div>
    </div>
</div>

@endsection
