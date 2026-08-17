@extends('layout.app')

@section('title', 'Produktquellen')

@section('header_actions')
    <a href="{{ route('source.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Neue Quelle
    </a>
@endsection

@section('content')
<div class="cis-card p-0 overflow-hidden">
    <table class="cis-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Ansprechpartner</th>
                <th>Kontakt</th>
                <th>Website</th>
                <th class="text-right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sources as $source)
            <tr>
                <td>
                    <a href="{{ route('source.edit', $source) }}"
                       class="font-medium text-gray-900 hover:text-primary-600 transition-colors">
                        {{ $source->name }}
                    </a>
                    @if($source->notes)
                        <p class="text-xs text-gray-400 truncate max-w-xs mt-0.5">{{ $source->notes }}</p>
                    @endif
                </td>
                <td class="text-sm text-gray-600">{{ $source->contact_name ?? '–' }}</td>
                <td class="text-sm text-gray-500">
                    @if($source->contact_email)
                        <a href="mailto:{{ $source->contact_email }}"
                           class="hover:text-primary-600">{{ $source->contact_email }}</a>
                    @endif
                    @if($source->contact_email && $source->contact_phone)
                        <span class="text-gray-300 mx-1">·</span>
                    @endif
                    @if($source->contact_phone)
                        <span>{{ $source->contact_phone }}</span>
                    @endif
                    @if(!$source->contact_email && !$source->contact_phone)
                        –
                    @endif
                </td>
                <td class="text-sm">
                    @if($source->url)
                        <a href="{{ $source->url }}" target="_blank" rel="noopener"
                           class="text-primary-600 hover:underline truncate max-w-[180px] inline-block">
                            {{ parse_url($source->url, PHP_URL_HOST) ?? $source->url }}
                            <i class="fa fa-arrow-up-right-from-square text-[10px] ml-0.5"></i>
                        </a>
                    @else
                        <span class="text-gray-400">–</span>
                    @endif
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('source.edit', $source) }}" class="btn btn-ghost btn-sm">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="{{ route('source.delete', $source) }}" class="btn btn-ghost btn-sm text-red-500">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-16 text-gray-400">
                    <i class="fa fa-truck text-4xl mb-3 block"></i>
                    <p class="text-sm font-medium">Noch keine Produktquellen angelegt.</p>
                    <a href="{{ route('source.create') }}" class="btn btn-primary btn-sm mt-4">
                        Erste Quelle erstellen
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
