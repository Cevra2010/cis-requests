@extends("layout.app")

@section("content")

@section("title","Konten")

<h1 class="cis-headline">Kontenübersicht</h1>

<div class="flex space-x-3 mb-4">
    <a href="{{ route("user.create") }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-user-plus"></i>
            <p class="text-xs">Konto erstellen</p>
        </div>
    </a>
    <a href="{{ route("user.role.index") }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-scale-balanced"></i>
            <p class="text-xs">Rollenverwaltung</p>
        </div>
    </a>
</div>
@include("layout.error_success")
@livewire("user.user-table")

@endsection
