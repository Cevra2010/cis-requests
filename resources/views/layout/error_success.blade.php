@if(session()->has("success"))
    <div class="bg-green-200 border border-green-400 text-green-700 rounded shadow p-2 text-xs mb-2">
        {{ session()->get("success") }}
    </div>
@endif

@if($errors->count())
    <div class="bg-red-200 border border-red-400 text-red-700 rounded shadow p-2 text-xs mb-2">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
