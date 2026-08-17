<div>
    <div class="cis-table">
        <table>
            <thead>
                <tr>
                    <th>Projektname</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                    <tr class="cursor-pointer" onclick='location.href="{{ route("project.edit", $project->cis_row_id) }}"'>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 shrink-0">
                                    <i class="fa fa-folder text-blue-500 text-sm"></i>
                                </div>
                                <span class="font-medium text-gray-900">{{ $project->name }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="cis-badge cis-badge-blue">
                                {{ $project->getStatusText() }}
                            </span>
                        </td>
                        <td class="text-gray-500 text-sm">{{ $project->created_at->format('d.m.Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('project.edit', $project->cis_row_id) }}"
                               onclick="event.stopPropagation()"
                               class="btn-secondary btn-sm">
                                <i class="fa fa-pen-to-square"></i>
                                Bearbeiten
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-gray-400">
                            <i class="fa fa-folder-open text-3xl mb-3 block"></i>
                            <p class="text-sm">Noch keine Projekte angelegt.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
