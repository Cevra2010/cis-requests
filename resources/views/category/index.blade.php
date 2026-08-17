@extends('layout.app')

@section('header_actions')
    <a href="{{ route('category.create', ['type' => $activeType]) }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neue Kategorie
    </a>
@endsection

@section('content')
<div class="flex gap-8">

    {{-- Typ-Auswahl links --}}
    <div class="w-52 shrink-0">
        <div class="cis-card p-0 overflow-hidden">
            <p class="px-4 py-2.5 text-[10px] font-semibold uppercase tracking-widest text-gray-500 border-b border-gray-100">
                Kategorie-Typen
            </p>
            @foreach($types as $typeKey => $typeMeta)
            <a href="{{ route('category.index', ['type' => $typeKey]) }}"
               class="flex items-center justify-between px-4 py-2.5 text-sm border-b border-gray-50 transition-colors
                      {{ $activeType === $typeKey ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                <span>{{ $typeMeta['label'] }}</span>
                @if($typeMeta['module'])
                    <span class="text-[10px] text-gray-400">{{ $typeMeta['module'] }}</span>
                @endif
            </a>
            @endforeach
        </div>
    </div>

    {{-- Kategorien --}}
    <div class="flex-1">
        <div class="cis-card p-0 overflow-hidden">
            <table class="cis-table">
                <thead>
                    <tr>
                        <th class="w-8">#</th>
                        <th>Name</th>
                        <th>Beschreibung</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody id="sortable-categories">
                    @forelse($categories as $cat)
                    <tr class="cursor-grab" data-id="{{ $cat->id }}">
                        <td class="text-gray-300">
                            <i class="fa fa-grip-vertical"></i>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                @if($cat->color)
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $cat->color }}"></span>
                                @endif
                                <span class="font-medium text-gray-900">{{ $cat->name }}</span>
                            </div>
                        </td>
                        <td class="text-gray-500 text-sm">{{ $cat->description ?? '–' }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('category.edit', $cat->id) }}" class="btn btn-ghost btn-sm">
                                    <i class="fa fa-pencil"></i>
                                </a>
                                <a href="{{ route('category.delete', $cat->id) }}" class="btn btn-ghost btn-sm text-red-500">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-gray-400">
                            <i class="fa fa-tag text-3xl mb-2 block"></i>
                            <p class="text-sm">Noch keine Kategorien angelegt.</p>
                            <a href="{{ route('category.create', ['type' => $activeType]) }}"
                               class="btn btn-primary btn-sm mt-3">Erste Kategorie erstellen</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
