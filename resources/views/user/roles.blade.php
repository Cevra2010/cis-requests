@extends("layout.app")

@section("content")

@section("title","Rollen: ".$user->firstname." ".$user->lastname)
<a href="{{ route("user.edit",$user) }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Rollen: {{ $user->firstname }} {{ $user->lastname }}</h1>

@livewire("user.role-selector",['user' => $user])
@endsection
