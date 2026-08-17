@extends('layout.app')

@section('title', 'Produktdatensätze')

@section('header_actions')
    <a href="{{ route('source') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-truck mr-1"></i> Produktquellen
    </a>
    <a href="{{ route('product.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neues Produkt
    </a>
@endsection

@section('content')
@livewire('product.product-table')
@endsection
