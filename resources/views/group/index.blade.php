@extends('layout.app')

@section('header_actions')
    <a href="{{ route('group.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neue Gruppe
    </a>
@endsection

@section('content')
@livewire('group.group-table')
@endsection
