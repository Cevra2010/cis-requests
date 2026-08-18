@extends('layout.public')

@section('title', 'Wareneingang')

@section('content')
@livewire('wareneingang.goods-receipt-checklist', ['token' => $token])
@endsection
