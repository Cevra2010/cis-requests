@extends('layout.app')

@section('title', 'Quelle löschen')

@section('content')
<div class="max-w-md">
    <div class="cis-card border border-red-200">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fa fa-triangle-exclamation text-red-600"></i>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Produktquelle löschen</h2>
                <p class="text-sm text-gray-500 mt-1">
                    <strong>{{ $source->name }}</strong> wird gelöscht.
                    @if($priceCount > 0)
                        <span class="text-amber-600 font-medium">
                            Diese Quelle ist bei {{ $priceCount }} Preiseinträgen hinterlegt.
                            Diese Preisdaten bleiben erhalten, die Quellzuordnung wird entfernt.
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('source.destroy', $source) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Endgültig löschen</button>
            </form>
            <a href="{{ route('source.edit', $source) }}" class="btn btn-ghost">Abbrechen</a>
        </div>
    </div>
</div>
@endsection
