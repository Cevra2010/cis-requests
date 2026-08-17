@extends('layout.app')

@section('title', 'Eigenschaft löschen')

@section('content')
<div class="max-w-lg">
    <div class="cis-card">
        <div class="flex items-start gap-4 mb-5">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 shrink-0">
                <i class="fa fa-triangle-exclamation text-red-500"></i>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Eigenschaft löschen</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Soll <strong>{{ $property->name }}</strong> dauerhaft gelöscht werden?
                </p>
                @if($usageCount > 0)
                    <p class="text-sm text-amber-600 mt-2">
                        <i class="fa fa-exclamation-circle mr-1"></i>
                        Diese Eigenschaft ist {{ $usageCount }} Projekt(en) zugeordnet und wird dort ebenfalls entfernt.
                    </p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('property.destroy', $property) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Jetzt löschen</button>
            </form>
            <a href="{{ route('property.index') }}" class="btn btn-ghost">Abbrechen</a>
        </div>
    </div>
</div>
@endsection
