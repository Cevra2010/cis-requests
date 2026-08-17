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
        <p class="text-sm text-gray-500">Gruppen- und Rollenzugehörigkeit</p>
    </div>

    <form action="{{ route('user.membership.update', $user) }}" method="POST" class="space-y-4">
        @csrf

        {{-- Gruppen --}}
        <div class="cis-card">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fa fa-users mr-1 text-primary-500"></i> Gruppen
            </h3>
            @if($groups->isNotEmpty())
            <div class="divide-y divide-gray-100">
                @foreach($groups as $group)
                <label class="flex items-center gap-3 py-2.5 cursor-pointer hover:bg-gray-50 -mx-4 px-4">
                    <input type="checkbox" name="groups[]" value="{{ $group->cis_row_id }}"
                           class="rounded text-primary-600 focus:ring-primary-500"
                           {{ in_array($group->cis_row_id, $userGroupIds) ? 'checked' : '' }}>
                    <div class="flex items-center gap-2 flex-1">
                        @if($group->color)
                            <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $group->color }}"></span>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $group->name }}</p>
                            @if($group->description)
                                <p class="text-xs text-gray-500">{{ $group->description }}</p>
                            @endif
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400">Noch keine Gruppen angelegt.
                    <a href="{{ route('group.create') }}" class="text-primary-600 hover:underline">Gruppe erstellen</a>
                </p>
            @endif
        </div>

        {{-- Rollen --}}
        <div class="cis-card">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fa fa-id-badge mr-1 text-purple-500"></i> Rollen
            </h3>
            @if($roles->isNotEmpty())
            <div class="divide-y divide-gray-100">
                @foreach($roles as $role)
                <label class="flex items-center gap-3 py-2.5 cursor-pointer hover:bg-gray-50 -mx-4 px-4">
                    <input type="checkbox" name="roles[]" value="{{ $role->cis_row_id }}"
                           class="rounded text-primary-600 focus:ring-primary-500"
                           {{ in_array($role->cis_row_id, $userRoleIds) ? 'checked' : '' }}>
                    <div class="flex items-center gap-2 flex-1">
                        @if($role->color)
                            <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $role->color }}"></span>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $role->name }}</p>
                            @if($role->description)
                                <p class="text-xs text-gray-500">{{ $role->description }}</p>
                            @endif
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400">Noch keine Rollen angelegt.
                    <a href="{{ route('role.create') }}" class="text-primary-600 hover:underline">Rolle erstellen</a>
                </p>
            @endif
        </div>

        <div>
            <button type="submit" class="btn btn-primary">Speichern</button>
        </div>
    </form>
</div>
@endsection
