@extends('layout.app')

@section('title', 'Projekte')

@section('header_actions')
    <a href="{{ route('project.trash') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-trash-can-arrow-up mr-1"></i> Papierkorb
    </a>
    <a href="{{ route('project.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-folder-plus mr-1"></i> Neues Projekt
    </a>
@endsection

@section('content')
@livewire('project.project-table')
@endsection
