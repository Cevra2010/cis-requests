@extends("layout.app")

@section("content")

@section("title","Produkte")
<a href="{{ route("project.edit",$project) }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Produkte verwalten</h1>

@include("layout.error_success")

@livewire("project.product-editor",['project' => $project])

@endsection
