@extends("layout.app")

@section("content")

@section("title","Projekte")

<h1 class="cis-headline">Projektübersicht</h1>

<div class="flex space-x-3 mb-4">
    <a href="{{ route("project.create") }}">
        <div class="btn-add h-full text-white w-min p-3 text-center">
            <i class="fa fa-folder-plus"></i>
            <p class="text-xs">Projekt anlegen</p>
        </div>
    </a>
</div>
@include("layout.error_success")
@livewire("project.project-map")

@endsection
