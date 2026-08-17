@extends('layout.app')

@section('header_actions')
    <a href="{{ route('group.index') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-users mr-1"></i> Gruppen
    </a>
    <a href="{{ route('role.index') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-id-badge mr-1"></i> Rollen
    </a>
    <a href="{{ route('user.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-user-plus mr-1"></i> Neuer Benutzer
    </a>
@endsection

@section('content')
<div class="cis-card">
    <table class="cis-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>E-Mail</th>
                <th>Gruppen</th>
                <th>Rollen</th>
                <th class="text-right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr onclick="location.href='{{ route('user.edit', $user) }}'" class="cursor-pointer">
                <td class="font-medium text-gray-900">{{ $user->name() }}</td>
                <td class="text-gray-500 text-sm">{{ $user->email }}</td>
                <td>
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->groups as $group)
                            <span class="cis-badge text-white text-xs"
                                  style="background: {{ $group->color ?? '#6B7280' }}">
                                {{ $group->name }}
                            </span>
                        @endforeach
                    </div>
                </td>
                <td>
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->roles as $role)
                            <span class="cis-badge text-white text-xs"
                                  style="background: {{ $role->color ?? '#8B5CF6' }}">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                </td>
                <td class="text-right" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('user.edit.membership', $user) }}" class="btn btn-ghost btn-sm" title="Gruppen & Rollen">
                            <i class="fa fa-users"></i>
                        </a>
                        <a href="{{ route('user.permissions', $user) }}" class="btn btn-ghost btn-sm" title="Berechtigungen">
                            <i class="fa fa-shield-halved"></i>
                        </a>
                        <a href="{{ route('user.edit', $user) }}" class="btn btn-ghost btn-sm" title="Bearbeiten">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="{{ route('user.delete', $user) }}" class="btn btn-ghost btn-sm text-red-500" title="Löschen">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-12 text-gray-400">
                    <i class="fa fa-user text-3xl mb-2 block"></i>
                    <p class="text-sm">Noch keine Benutzer angelegt.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
