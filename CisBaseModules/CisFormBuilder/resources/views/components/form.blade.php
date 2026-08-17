@php
use CisFoundation\CisFormBuilder\CisFormBuilder;
use CisFoundation\CisFormBuilder\CisField;

$formDef = CisFormBuilder::get($name);
$fields  = $formDef->getFields();
$method  = strtoupper($formDef->method);
$action  = $formDef->action ?? '';
@endphp

<form
    action="{{ $action }}"
    method="{{ in_array($method, ['GET', 'POST']) ? $method : 'POST' }}"
    {{ $attributes }}
>
    @csrf
    @if(!in_array($method, ['GET', 'POST']))
        @method($method)
    @endif

    <div class="{{ $formDef->getGridClass() }}">
        @foreach($fields as $field)
            @if($field->type === 'hidden')
                <input type="hidden"
                       name="{{ $field->key }}"
                       value="{{ old($field->key, $field->getValue($model ?? null)) }}">
            @else
                <div class="{{ $field->width ?? '' }}">
                    <x-cis-field :field="$field" :model="$model ?? null" />
                </div>
            @endif
        @endforeach
    </div>

    {{ $slot }}
</form>
