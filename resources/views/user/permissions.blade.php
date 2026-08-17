@extends('layout.app')

@section('header_actions')
    <a href="{{ route('user.edit', $user) }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-arrow-left mr-1"></i> Zurück
    </a>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">{{ $user->name() }}</h2>
        <p class="text-sm text-gray-500">Explizite Berechtigungen – überschreiben Gruppen- und Rollenzuweisungen</p>
    </div>

    <div class="cis-card mb-4 bg-blue-50 border border-blue-200">
        <div class="flex gap-3 text-sm text-blue-700">
            <i class="fa fa-circle-info mt-0.5 shrink-0"></i>
            <div>
                <p class="font-medium mb-1">Wie funktioniert das?</p>
                <ul class="space-y-0.5 text-blue-600">
                    <li><strong>Vererbt</strong> – kein Override, Gruppen- und Rollenrechte gelten</li>
                    <li><strong>Erlaubt</strong> – explizit gewährt, auch wenn keine Gruppe es hat</li>
                    <li><strong>Verboten</strong> – explizit verweigert, gewinnt gegen alles</li>
                </ul>
            </div>
        </div>
    </div>

    <form action="{{ route('user.permissions.update', $user) }}" method="POST">
        @csrf

        @include('partials.permission-matrix')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Berechtigungen speichern</button>
        </div>
    </form>
</div>
@endsection
