@extends("layout.app")

@section("content")

@section("title","Rolle bearbeiten")
<a href="{{ route("user.role.index") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Rolle bearbeiten: {{ $role->name }}</h1>
<div class="flex space-x-3 mb-4">
    @access("role.edit.delete")
    <a href="{{ route("user.role.edit.delete",$role) }}">
        <div class="bg-slate-100 hover:bg-slate-200 h-full text-slate-600 w-min p-3 rounded text-center">
            <i class="fa fa-trash"></i>
            <p class="text-xs">Rolle löschen</p>
        </div>
    </a>
    @endaccess
</div>
<p class="text-xs text-slate-600 mb-4">Konten in dieser Rolle: {{ $role->users()->count() }} | Erstellt: {{ $role->created_at->format("d.m.Y H:i ")}} | letzte Änderung: {{ $role->updated_at->format("d.m.Y H:i") }}
@include("layout.error_success")

@foreach($areas as $parentArea)
    <div class="flex p-2 pl-4 border border-slate-200 bg-slate-100 text-slate-700">
        <div>
            @livewire("role.area-toggle",['area' => $parentArea,'role' => $role])
        </div>

        <div class="pl-4">{{ $parentArea->name }}</div>
    </div>
    @include("role.area-child",['deep' => 1])
@endforeach

@endsection
