<div>
    @include("layout.error_success")
    <div class="cis-form-group flex items-center">
        <input type="text" wire:keydown.enter='store' @error('parameter') class="is-invalid" @enderror wire:model='newParameterText' placeholder="Parameter hinzufügen..." autofocus>
        <a href="#add" wire:click='store'  class="pl-5 text-2xl"><i class="fa fa-plus-circle"></i></a>
    </div>
    <ul>
        @foreach($parameters as $parameter)
            <li class="p-2 border-b"><a href="#del" wire:click='delete({{ $parameter }})'><i class="text-red-600 pr-2 fa fa-minus-circle"></i></a> {{ $parameter->text }}</li>
        @endforeach
    </ul>
</div>
