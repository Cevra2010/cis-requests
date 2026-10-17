<div>
    <label class="cis-label">Zielschritt</label>
    <select wire:model="config.target" class="cis-input w-full">
        <option value="next">Nächster Schritt</option>
        <option value="previous">Vorheriger Schritt</option>
        <optgroup label="Bestimmter Schritt">
            @foreach($stepOptions as $option)
                @continue($option->cis_row_id === $ownStepId)
                <option value="{{ $option->code }}">{{ $option->label }}{{ $option->is_active ? '' : ' (deaktiviert)' }}</option>
            @endforeach
        </optgroup>
    </select>
    @if(isset($formErrors['target']))<p class="mt-1 text-xs text-red-600">{{ $formErrors['target'] }}</p>@endif
    <p class="mt-2 text-xs text-gray-400">
        Der Wechsel erfolgt automatisch (ohne Freigabe) und erscheint im Projektverlauf als „automatisch“.
        Die Maßnahmen des Zielschritts werden dabei ausgeführt.
    </p>
</div>
