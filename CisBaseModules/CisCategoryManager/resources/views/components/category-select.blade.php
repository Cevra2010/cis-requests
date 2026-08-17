@php
use CisFoundation\CisCategoryManager\CisCategoryManager;
$categories = CisCategoryManager::forType($type);
@endphp

<select
    name="{{ $name }}"
    id="{{ $name }}"
    {{ $attributes->merge(['class' => 'cis-input w-full']) }}
>
    @if(!($required ?? false))
        <option value="">— Keine Kategorie —</option>
    @endif
    @foreach($categories as $cat)
        <option value="{{ $cat->id }}"
            @if((string)($value ?? '') === (string)$cat->id) selected @endif>
            {{ $cat->name }}
        </option>
    @endforeach
</select>

@if($categories->isEmpty())
    <p class="mt-1 text-xs text-amber-600">
        <i class="fa fa-triangle-exclamation mr-1"></i>
        Noch keine Kategorien vom Typ „{{ CisCategoryManager::getTypeLabel($type) }}" angelegt.
        <a href="{{ route('category.index', ['type' => $type]) }}" class="underline">Jetzt anlegen</a>
    </p>
@endif
