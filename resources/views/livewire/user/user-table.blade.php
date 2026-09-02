<div>
    <div class="flex items-center gap-2 mb-4">
        <span class="text-xs text-gray-400">{{ $rows->total() }} Benutzer</span>
        @if(count($filters))
            <button wire:click="resetAllFilters" class="btn-ghost btn-sm">
                <i class="fa fa-xmark"></i>
                Alle Filter zurücksetzen
            </button>
        @endif
        <div class="ml-auto">
            <x-data-table.per-page-select />
        </div>
    </div>

    <div class="cis-table">
        <table>
            <thead>
                <tr>
                    <x-data-table.th field="name" label="Name" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('name')" :active="$filters['name'] ?? []" />
                    <x-data-table.th field="email" label="E-Mail" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('email')" :active="$filters['email'] ?? []" />
                    <x-data-table.th field="groups" label="Gruppen" filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('groups')" :active="$filters['groups'] ?? []" />
                    <x-data-table.th field="roles" label="Rollen" filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('roles')" :active="$filters['roles'] ?? []" />
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $user)
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
                        <p class="text-sm">Keine Benutzer gefunden.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->links() }}</div>
</div>
