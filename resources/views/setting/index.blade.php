@extends('layout.app')

@section('title', 'Einstellungen')

@section('content')
    @livewire('setting.general-settings')
    @can('system.reset')
        @livewire('setting.danger-zone')
    @endcan
@endsection
