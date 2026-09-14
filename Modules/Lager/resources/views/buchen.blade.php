@extends('layout.app')

@section('title', 'Ware buchen')

@section('content')
    @livewire('lager.lager-buchen', ['lagerortId' => $lagerortId])
@endsection
