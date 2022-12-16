@extends("layout.app")

@section("content")

@section("title","Produktübersicht")

<h1 class="cis-headline">Produktübersicht</h1>

@access("source.create")
<div class="flex space-x-3 mb-4">
    <a href="{{ route("source.create") }}">
        <div class="btn-add w-min p-3 text-center">
            <i class="fa fa-circle-plus"></i>
            <p class="text-xs">Produktquelle hinzufügen</p>
        </div>
    </a>
</div>
@endaccess
@include("layout.error_success")
@livewire("source.source-table")

@endsection
