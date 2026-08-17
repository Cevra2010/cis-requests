@extends('layout.app')

@section('title', 'Eigenschaften')

@section('header_actions')
    <a href="{{ route('property.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neue Eigenschaft
    </a>
@endsection

@section('content')

<div class="cis-card p-0 overflow-hidden">
    <table class="cis-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Beschreibungstext</th>
                <th class="text-right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($properties as $property)
            <tr>
                <td>
                    <span class="font-medium text-gray-900">{{ $property->name }}</span>
                </td>
                <td class="text-sm text-gray-500 max-w-md">
                    @if($property->description)
                        <span class="line-clamp-2">{{ $property->description }}</span>
                    @else
                        <span class="text-gray-300 italic">Kein Text hinterlegt</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('property.edit', $property) }}" class="btn btn-ghost btn-sm" title="Bearbeiten">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="{{ route('property.delete', $property) }}" class="btn btn-ghost btn-sm text-red-400" title="Löschen">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center py-16 text-gray-400">
                    <i class="fa fa-list-check text-4xl mb-3 block"></i>
                    <p class="text-sm font-medium">Noch keine Eigenschaften angelegt.</p>
                    <a href="{{ route('property.create') }}" class="btn btn-primary btn-sm mt-4">
                        Erste Eigenschaft anlegen
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
