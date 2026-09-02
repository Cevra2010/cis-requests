<label class="flex items-center gap-1.5 text-xs text-gray-500">
    Pro Seite
    <select wire:model.live="perPage" class="cis-input py-1 text-xs">
        @foreach([25, 50, 100, 250] as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>
</label>
