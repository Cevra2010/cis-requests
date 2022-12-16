@extends("layout.app")

@section("content")

@section("title",$project->name)

<h1 class="cis-headline">Projekt: {{ $project->name }}</h1>

@include("layout.error_success")

<div class="flex space-x-3 mb-4">
    <a href="{{ route("project.edit.products",$project->cis_row_id) }}">
        <div class="btn-add w-min p-3 text-center">
            <i class="fa fa-box-archive"></i>
            <p class="text-xs">Produkte verwalten</p>
        </div>
    </a>
    <a href="{{ route("project.map.preview",$project) }}">
        <div class="btn-nav h-full text-white w-min p-3 text-center">
            <i class="fa fa-eye"></i>
            <p class="text-xs">Projektmappe: Vorschau</p>
        </div>
    </a>
</div>

<div class="bg-slate-200 rounded">
    <div class="text-slate-700 font-light p-3 border-b border-slate-300 flex items-center">
        <div>Projektübersicht</div>
        <div class="ml-auto flex items-center space-x-4">
            <p>Projektstatus</p>
            <div class="bg-{{ $project->getStatusColor() }} rounded text-white p-1">
                {{ $project->getStatusText() }}
            </div>
        </div>
    </div>
    <div class="p-3">
        oiawdjo 
    </div>
    
</div>

@endsection
