@extends('layout.app')

@section('header_actions')
    <a href="{{ route('group.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neue Gruppe
    </a>
@endsection

@section('content')
<div class="cis-card">
    <table class="cis-table">
        <thead>
            <tr>
                <th>Gruppe</th>
                <th>Beschreibung</th>
                <th class="text-center w-24">Mitglieder</th>
                <th class="text-right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
            <tr onclick="location.href='{{ route('group.edit', $group) }}'" class="cursor-pointer">
                <td>
                    <div class="flex items-center gap-2">
                        @if($group->color)
                            <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $group->color }}"></span>
                        @endif
                        <span class="font-medium text-gray-900">{{ $group->name }}</span>
                    </div>
                </td>
                <td class="text-gray-500 text-sm">{{ $group->description ?? '–' }}</td>
                <td class="text-center">
                    <span class="cis-badge cis-badge-gray">{{ $group->users_count }}</span>
                </td>
                <td class="text-right" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('group.permissions', $group) }}" class="btn btn-ghost btn-sm" title="Berechtigungen">
                            <i class="fa fa-shield-halved"></i>
                        </a>
                        <a href="{{ route('group.edit', $group) }}" class="btn btn-ghost btn-sm" title="Bearbeiten">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="{{ route('group.delete', $group) }}" class="btn btn-ghost btn-sm text-red-500" title="Löschen">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-12 text-gray-400">
                    <i class="fa fa-users text-3xl mb-2 block"></i>
                    <p class="text-sm">Noch keine Gruppen angelegt.</p>
                    <a href="{{ route('group.create') }}" class="btn btn-primary btn-sm mt-3">Erste Gruppe erstellen</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
