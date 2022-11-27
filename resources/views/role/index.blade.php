@extends("layout.app")

@section("content")

@section("title","Konten")
<a href="{{ route("user") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Rollenübersicht</h1>
<div class="flex space-x-3 mb-4">
    <a href="{{ route("user.role.create") }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-square-plus"></i>
            <p class="text-xs">Rolle erstellen</p>
        </div>
    </a>
</div>

@include("layout.error_success")
@livewire("role.role-table")

@endsection
