@php
/** @var \CisFoundation\CisFormBuilder\CisField $field */
$value    = old($field->key, $field->getValue($model ?? null));
$hasError = $errors->has($field->key);
$baseInput = 'cis-input w-full' . ($hasError ? ' border-red-500 focus:ring-red-500' : '');
@endphp

{{-- Custom renderer --}}
@if($field->hasCustomRenderer())
    {!! $field->renderCustom($model ?? null) !!}

{{-- Textarea --}}
@elseif($field->type === 'textarea')
    <div>
        <label class="cis-label" for="{{ $field->key }}">
            {{ $field->label }}
            @if($field->required) <span class="text-red-500 ml-0.5">*</span> @endif
        </label>
        <textarea
            id="{{ $field->key }}"
            name="{{ $field->key }}"
            class="{{ $baseInput }}"
            rows="4"
            @if($field->placeholder) placeholder="{{ $field->placeholder }}" @endif
            @if($field->disabled) disabled @endif
            @if($field->readonly) readonly @endif
            {!! $field->getAttributeString() !!}
        >{{ $value }}</textarea>
        @if($field->help && !$hasError)
            <p class="mt-1 text-xs text-gray-500">{{ $field->help }}</p>
        @endif
        @error($field->key)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

{{-- Select --}}
@elseif($field->type === 'select')
    <div>
        <label class="cis-label" for="{{ $field->key }}">
            {{ $field->label }}
            @if($field->required) <span class="text-red-500 ml-0.5">*</span> @endif
        </label>
        <select
            id="{{ $field->key }}"
            name="{{ $field->key }}"
            class="{{ $baseInput }}"
            @if($field->disabled) disabled @endif
            {!! $field->getAttributeString() !!}
        >
            @if(!$field->required)
                <option value="">— Bitte wählen —</option>
            @endif
            @foreach($field->getOptions() as $option)
                <option value="{{ $option['value'] }}"
                    {{ (string)$value === (string)$option['value'] ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
        @if($field->help && !$hasError)
            <p class="mt-1 text-xs text-gray-500">{{ $field->help }}</p>
        @endif
        @error($field->key)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

{{-- Radio group --}}
@elseif($field->type === 'radio')
    <div>
        <label class="cis-label">
            {{ $field->label }}
            @if($field->required) <span class="text-red-500 ml-0.5">*</span> @endif
        </label>
        <div class="mt-1 space-y-1.5">
            @foreach($field->getOptions() as $option)
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="radio"
                           name="{{ $field->key }}"
                           value="{{ $option['value'] }}"
                           class="text-primary-600 focus:ring-primary-500"
                           {{ (string)$value === (string)$option['value'] ? 'checked' : '' }}
                           @if($field->disabled) disabled @endif>
                    {{ $option['label'] }}
                </label>
            @endforeach
        </div>
        @if($field->help && !$hasError)
            <p class="mt-1 text-xs text-gray-500">{{ $field->help }}</p>
        @endif
        @error($field->key)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

{{-- Checkbox (single boolean) --}}
@elseif($field->type === 'checkbox')
    <div class="flex items-start gap-3">
        <input type="hidden" name="{{ $field->key }}" value="0">
        <input
            type="checkbox"
            id="{{ $field->key }}"
            name="{{ $field->key }}"
            value="1"
            class="mt-0.5 rounded text-primary-600 focus:ring-primary-500 border-gray-300"
            {{ $value ? 'checked' : '' }}
            @if($field->disabled) disabled @endif
            {!! $field->getAttributeString() !!}
        >
        <div>
            <label class="text-sm font-medium text-gray-700 cursor-pointer" for="{{ $field->key }}">
                {{ $field->label }}
                @if($field->required) <span class="text-red-500 ml-0.5">*</span> @endif
            </label>
            @if($field->help)
                <p class="text-xs text-gray-500">{{ $field->help }}</p>
            @endif
            @error($field->key)
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

{{-- Toggle (styled checkbox) --}}
@elseif($field->type === 'toggle')
    <div class="flex items-center gap-3">
        <input type="hidden" name="{{ $field->key }}" value="0">
        <button
            type="button"
            role="switch"
            aria-checked="{{ $value ? 'true' : 'false' }}"
            onclick="this.setAttribute('aria-checked', this.getAttribute('aria-checked')==='true'?'false':'true'); this.nextElementSibling.checked = !this.nextElementSibling.checked;"
            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1
                   {{ $value ? 'bg-primary-600' : 'bg-gray-200' }}"
            @if($field->disabled) disabled @endif
        >
            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                         {{ $value ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
        </button>
        <input type="checkbox" name="{{ $field->key }}" value="1" class="sr-only"
               id="{{ $field->key }}" {{ $value ? 'checked' : '' }}>
        <label class="text-sm font-medium text-gray-700 cursor-pointer" for="{{ $field->key }}">
            {{ $field->label }}
        </label>
        @error($field->key)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

{{-- Default: text, email, password, number, url, date --}}
@else
    <div>
        <label class="cis-label" for="{{ $field->key }}">
            {{ $field->label }}
            @if($field->required) <span class="text-red-500 ml-0.5">*</span> @endif
        </label>
        <input
            type="{{ $field->type }}"
            id="{{ $field->key }}"
            name="{{ $field->key }}"
            class="{{ $baseInput }}"
            value="{{ $value }}"
            @if($field->placeholder) placeholder="{{ $field->placeholder }}" @endif
            @if($field->disabled) disabled @endif
            @if($field->readonly) readonly @endif
            {!! $field->getAttributeString() !!}
        >
        @if($field->help && !$hasError)
            <p class="mt-1 text-xs text-gray-500">{{ $field->help }}</p>
        @endif
        @error($field->key)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endif
