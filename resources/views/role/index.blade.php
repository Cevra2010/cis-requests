@extends('layout.app')

@section('header_actions')
    <a href="{{ route('role.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neue Rolle
    </a>
@endsection

@section('content')
<div class="cis-card">
    <table class="cis-table">
        <thead>
            <tr>
                <th>Rolle</th>
                <th>Beschreibung</th>
                <th class="text-center w-24">Benutzer</th>
                <th class="text-right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($roles as $role)
            <tr onclick="location.href='{{ route('role.edit', $role) }}'" class="cursor-pointer">
                <td>
                    <div class="flex items-center gap-2">
                        @if($role->color)
                            <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $role->color }}"></span>
                        @endif
                        <span class="font-medium text-gray-900">{{ $role->name }}</span>
                    </div>
                </td>
                <td class="text-gray-500 text-sm">{{ $role->description ?? '–' }}</td>
                <td class="text-center">
                    <span class="cis-badge cis-badge-gray">{{ $role->users_count }}</span>
                </td>
                <td class="text-right" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('role.permissions', $role) }}" class="btn btn-ghost btn-sm" title="Berechtigungen">
                            <i class="fa fa-shield-halved"></i>
                        </a>
                        <a href="{{ route('role.edit', $role) }}" class="btn btn-ghost btn-sm" title="Bearbeiten">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="{{ route('role.delete', $role) }}" class="btn btn-ghost btn-sm text-red-500" title="Löschen">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-12 text-gray-400">
                    <i class="fa fa-id-badge text-3xl mb-2 block"></i>
                    <p class="text-sm">Noch keine Rollen angelegt.</p>
                    <a href="{{ route('role.create') }}" class="btn btn-primary btn-sm mt-3">Erste Rolle erstellen</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
