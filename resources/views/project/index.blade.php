@extends('layout.app')

@section('title', 'Projekte')

@section('header_actions')
    <a href="{{ route('project.trash') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-trash-can-arrow-up mr-1"></i> Papierkorb
    </a>
    <a href="{{ route('project.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-folder-plus mr-1"></i> Neues Projekt
    </a>
@endsection

@section('content')

{{-- Filter bar --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
    <form method="GET" action="{{ route('project') }}" class="flex flex-wrap items-center gap-2">
        <select name="status" class="cis-input py-1.5 text-sm" onchange="this.form.submit()">
            <option value="">Alle Status</option>
            @foreach(\App\Models\Project::STATUSES as $code => $meta)
                <option value="{{ $code }}" {{ $statusFilter === $code ? 'selected' : '' }}>
                    {{ $meta['label'] }}
                </option>
            @endforeach
        </select>

        @if($categories->isNotEmpty())
        <select name="category" class="cis-input py-1.5 text-sm" onchange="this.form.submit()">
            <option value="">Alle Kategorien</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string)$categoryFilter === (string)$cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        @endif

        @if($statusFilter || $categoryFilter)
            <a href="{{ route('project') }}" class="text-xs text-gray-500 hover:text-gray-800">
                <i class="fa fa-times mr-1"></i>Filter zurücksetzen
            </a>
        @endif
    </form>

    <span class="ml-auto text-xs text-gray-400">{{ $projects->count() }} Projekt(e)</span>
</div>

{{-- Project table --}}
<div class="cis-card p-0 overflow-hidden">
    <table class="cis-table">
        <thead>
            <tr>
                <th>Projektname</th>
                <th>Status</th>
                <th>Kategorie</th>
                <th>Verantwortlich</th>
                <th>Auftraggeber</th>
                <th>Fällig</th>
                <th class="text-right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
            <tr>
                <td>
                    <a href="{{ route('project.show', $project->cis_row_id) }}"
                       class="font-medium text-gray-900 hover:text-primary-600 transition-colors">
                        {{ $project->name }}
                    </a>
                    @if($project->description)
                        <p class="text-xs text-gray-400 truncate max-w-xs">{{ $project->description }}</p>
                    @endif
                </td>
                <td>
                    @php $color = $project->statusColor(); @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        {{ $color === 'green'  ? 'bg-green-100 text-green-700'   : '' }}
                        {{ $color === 'blue'   ? 'bg-blue-100 text-blue-700'     : '' }}
                        {{ $color === 'yellow' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $color === 'gray'   ? 'bg-gray-100 text-gray-600'     : '' }}
                    ">
                        {{ $project->statusLabel() }}
                    </span>
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
                        <form method="POST" action="{{ route('project.duplicate', $project->cis_row_id) }}"
                              class="inline">
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
                <td colspan="7" class="text-center py-16 text-gray-400">
                    <i class="fa fa-folder-open text-4xl mb-3 block"></i>
                    <p class="text-sm font-medium">Noch keine Projekte vorhanden.</p>
                    <a href="{{ route('project.create') }}" class="btn btn-primary btn-sm mt-4">
                        Erstes Projekt anlegen
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
