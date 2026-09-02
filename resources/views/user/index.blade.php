@extends('layout.app')

@section('header_actions')
    <a href="{{ route('group.index') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-users mr-1"></i> Gruppen
    </a>
    <a href="{{ route('role.index') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-id-badge mr-1"></i> Rollen
    </a>
    <a href="{{ route('user.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-user-plus mr-1"></i> Neuer Benutzer
    </a>
@endsection

@section('content')
@livewire('user.user-table')
@endsection
