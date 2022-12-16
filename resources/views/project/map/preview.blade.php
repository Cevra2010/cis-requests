@extends("layout.app")

@section("content")

@section("title","Vorschau Projektmappe")
<a href="{{ route("project.edit",$project) }}" class="text-slate-700"><i class="fa fa-angles-left"></i> zurück</a>
<h1 class="cis-headline">Vorschau - Projektmappe</h1>

@include("layout.error_success")

    <div class="mx-auto bg-white p-10 shadow-lg border" style="width: 21cm;">
        @include("project.map.header")

        @foreach($project->products()->withPivot(['product_count'])->get() as $product)
            <div class="text-xl font-bold mt-5">{{ $product->name }}</div>
            <hr>
            <div class="mt-2 text-sm">Stückzahl: <span class="font-light">{{ $product->pivot->product_count }}</span></div>
            @if($product->description())
                <div class="mt-4 text-sm">Beschreibung:</div>
                <div class="text-sm font-light">{!! nl2br($product->description()->text) !!}</div>
            @endif
            <div class="text-sm mt-4">
                <div class="mt-4 text-sm">Produktmerkmale:</div>
                <ul class="list-disc ml-10 font-light">
                    @foreach($product->parameters()->get() as $param)   
                        <li>{{ $param->text }}</li>
                    @endforeach
                </ul>       
            </div>
        @endforeach
    </div>
@endsection
