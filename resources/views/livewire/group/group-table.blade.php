<div>
    <div class="flex items-center gap-2 mb-4">
        <span class="text-xs text-gray-400">{{ $rows->total() }} Gruppe(n)</span>
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
                    <x-data-table.th field="name" label="Gruppe" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('name')" :active="$filters['name'] ?? []" />
                    <x-data-table.th field="description" label="Beschreibung" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('description')" :active="$filters['description'] ?? []" />
                    <x-data-table.th field="users_count" label="Mitglieder" sortable filterable align="center"
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('users_count')" :active="$filters['users_count'] ?? []" />
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $group)
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
                        <p class="text-sm">Keine Gruppen gefunden.</p>
                        <a href="{{ route('group.create') }}" class="btn btn-primary btn-sm mt-3">Erste Gruppe erstellen</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->links() }}</div>
</div>
