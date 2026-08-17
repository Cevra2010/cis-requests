@if(session()->has('success'))
    <div class="cis-success mb-4">
        <i class="fa fa-circle-check shrink-0"></i>
        <span>{{ session()->get('success') }}</span>
    </div>
@endif

@if($errors->count())
    <div class="cis-error mb-4">
        <i class="fa fa-circle-exclamation shrink-0"></i>
        <div>
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    </div>
@endif
