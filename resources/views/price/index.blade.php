@extends("layout.app")

@section("content")


@include("layout.error_success")
<h1 class="text-3xl mb-8">Toolbox - Preispflege</h1>
@livewire("price.price-editor")

@endsection
