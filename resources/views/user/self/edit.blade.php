@extends("layout.app")

@section("content")

@section("title","Konto bearbeiten")
<a href="{{ route("user") }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Mein Konto ({{ $user->name() }})</h1>

<div class="flex space-x-3 mb-4">

</div>
<hr class="mb-4">
<p class="text-xs text-slate-600 mb-4">Erstellt: {{ $user->created_at->format("d.m.Y H:i ")}} | letzte Änderung: {{ $user->updated_at->format("d.m.Y H:i") }}
@include("layout.error_success")
<form action="{{ route("dashboard.self.update") }}" method="POST">
    @csrf
    <div class="cis-form-group">
        <label for="email">E-Mail</label>
        <input type="text" name="email" value="{{ old("email",$user->email) }}" @error("email") class="is-invalid" @endif>
    </div>
    <button type="submit" class="cis-submit">Speichern</button>
</form>

@endsection
