@extends('layout.app')

@section('title', 'Produktquellen')

@section('header_actions')
    <a href="{{ route('source.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neue Quelle
    </a>
@endsection

@section('content')
@livewire('source.source-table')
@endsection
