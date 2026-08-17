@extends('layout.app')

@section('title', 'Produkt löschen')

@section('content')
<div class="max-w-md">
    <div class="cis-card border border-red-200">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fa fa-triangle-exclamation text-red-600"></i>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Produkt löschen</h2>
                <p class="text-sm text-gray-500 mt-1">
                    <strong>{{ $product->name }}</strong> wird in den Papierkorb verschoben.
                </p>
                @if($childCount > 0)
                    <p class="text-sm text-amber-600 mt-2 font-medium">
                        <i class="fa fa-triangle-exclamation mr-1"></i>
                        Dieses Produkt hat {{ $childCount }} Unterprodukt(e), die ebenfalls gelöscht werden.
                    </p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('product.edit.delete.store', $product) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">Produkt löschen</button>
            </form>
            <a href="{{ route('product.edit', $product) }}" class="btn btn-ghost">Abbrechen</a>
        </div>
    </div>
</div>
@endsection
