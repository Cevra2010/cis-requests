@extends('layout.app')

@section('title', 'Papierkorb – Projekte')

@section('header_actions')
    <a href="{{ route('project') }}" class="btn btn-ghost btn-sm">
        <i class="fa fa-arrow-left mr-1"></i> Zurück zur Liste
    </a>
@endsection

@section('content')
<div class="cis-card p-0 overflow-hidden">
    <table class="cis-table">
        <thead>
            <tr>
                <th>Projektname</th>
                <th>Gelöscht am</th>
                <th class="text-right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
            <tr class="opacity-75">
                <td>
                    <span class="font-medium text-gray-700">{{ $project->name }}</span>
                    @if($project->description)
                        <p class="text-xs text-gray-400 truncate max-w-sm">{{ $project->description }}</p>
                    @endif
                </td>
                <td class="text-sm text-gray-500">
                    {{ $project->deleted_at->format('d.m.Y H:i') }}
                </td>
                <td class="text-right">
                    <form method="POST"
                          action="{{ route('project.restore', $project->cis_row_id) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm text-green-600 hover:text-green-700">
                            <i class="fa fa-rotate-left mr-1"></i> Wiederherstellen
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center py-16 text-gray-400">
                    <i class="fa fa-trash-can text-4xl mb-3 block"></i>
                    <p class="text-sm">Der Papierkorb ist leer.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
