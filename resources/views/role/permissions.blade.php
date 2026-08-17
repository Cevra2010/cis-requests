@extends('layout.app')

@section('header_actions')
    <a href="{{ route('role.edit', $role) }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-arrow-left mr-1"></i> Zurück zur Rolle
    </a>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="mb-4 flex items-center gap-3">
        @if($role->color)
            <span class="w-4 h-4 rounded-full" style="background: {{ $role->color }}"></span>
        @endif
        <div>
            <h2 class="text-base font-semibold text-gray-900">{{ $role->name }}</h2>
            <p class="text-xs text-gray-500">Globale Berechtigungen – gelten für alle Projekte</p>
        </div>
    </div>

    <form action="{{ route('role.permissions.update', $role) }}" method="POST">
        @csrf

        @include('partials.permission-matrix')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Berechtigungen speichern</button>
        </div>
    </form>
</div>
@endsection
