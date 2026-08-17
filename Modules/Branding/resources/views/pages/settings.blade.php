@extends('layout.app')

@section('title', 'Branding')

@section('header_actions')
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Branding & Corporate Identity</h1>
        <p class="text-sm text-gray-500 mt-1">Logo, Kopf- und Fußzeile für Ausschreibungsdokumente konfigurieren.</p>
    </div>

    @livewire('branding.branding-settings')
</div>
@endsection
