<div>
    <div class="flex items-center gap-2 mb-4">
        <span class="text-xs text-gray-400">{{ $rows->total() }} Projekt(e)</span>
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

    <div class="cis-card p-0 overflow-hidden">
        <table class="cis-table">
            <thead>
                <tr>
                    <x-data-table.th field="name" label="Projektname" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('name')" :active="$filters['name'] ?? []" />
                    <x-data-table.th field="status" label="Status" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('status')" :active="$filters['status'] ?? []" />
                    <x-data-table.th field="locked" label="Ausschreibung" filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('locked')" :active="$filters['locked'] ?? []" />
                    <x-data-table.th field="category" label="Kategorie" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('category')" :active="$filters['category'] ?? []" />
                    <x-data-table.th field="assignee" label="Verantwortlich" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('assignee')" :active="$filters['assignee'] ?? []" />
                    <x-data-table.th field="client" label="Auftraggeber" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('client')" :active="$filters['client'] ?? []" />
                    <x-data-table.th field="due_date" label="Fällig" sortable filterable
                        :order-by="$orderBy" :order-direction="$orderDirection"
                        :options="$this->filterOptionsFor('due_date')" :active="$filters['due_date'] ?? []" />
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $project)
                <tr>
                    <td>
                        <a href="{{ route('project.show', $project->cis_row_id) }}"
                           class="font-medium text-gray-900 hover:text-primary-600 transition-colors">
                            @if($project->isVehicle())<i class="fa fa-truck-fast text-purple-400 mr-1" title="Fahrzeugausschreibung"></i>@endif
                            {{ $project->name }}
                        </a>
                        @if($project->description)
                            <p class="text-xs text-gray-400 truncate max-w-xs">{{ $project->description }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $project->statusBadgeClasses() }}">
                            {{ $project->statusLabel() }}
                        </span>
                    </td>
                    <td>
                        @if($project->isLocked())
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700"
                                  title="Fixiert am {{ $project->tender_locked_at?->format('d.m.Y H:i') }}">
                                <i class="fa fa-lock"></i> Fixiert
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                <i class="fa fa-pencil"></i> In Bearbeitung
                            </span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-600">{{ $project->categoryLabel() }}</td>
                    <td class="text-sm text-gray-600">{{ $project->assigneeLabel() }}</td>
                    <td class="text-sm text-gray-600">{{ $project->client ?? '–' }}</td>
                    <td class="text-sm text-gray-500">
                        {{ $project->due_date ? $project->due_date->format('d.m.Y') : '–' }}
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('project.show', $project->cis_row_id) }}" class="btn btn-ghost btn-sm" title="Öffnen">
                                <i class="fa fa-folder-open"></i>
                            </a>
                            <a href="{{ route('project.edit', $project->cis_row_id) }}" class="btn btn-ghost btn-sm" title="Bearbeiten">
                                <i class="fa fa-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('project.duplicate', $project->cis_row_id) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm" title="Duplizieren">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </form>
                            <form method="POST"
                                  action="{{ route('project.destroy', $project->cis_row_id) }}"
                                  class="inline"
                                  onsubmit="return confirm('Projekt „{{ addslashes($project->name) }}" in den Papierkorb verschieben?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm text-red-500">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-16 text-gray-400">
                        <i class="fa fa-folder-open text-4xl mb-3 block"></i>
                        <p class="text-sm font-medium">Keine Projekte gefunden.</p>
                        <a href="{{ route('project.create') }}" class="btn btn-primary btn-sm mt-4">
                            Erstes Projekt anlegen
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $rows->links() }}</div>
</div>
