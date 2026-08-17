<div class="max-w-md">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-800 mb-1">Beschaffung</h2>
        <p class="text-sm text-gray-500 mb-5">
            Globaler Standard für den Mindestbestellwert je Anbieter. Kann je Projekt überschrieben werden.
        </p>

        @if(session('success'))
            <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ session('success') }}</div>
        @endif

        <div>
            <label class="cis-label" for="defaultMinOrderValue">Mindestbestellwert (Standard)</label>
            <div class="relative">
                <input type="text" id="defaultMinOrderValue" wire:model="defaultMinOrderValue"
                       class="cis-input w-full pr-8">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">€</span>
            </div>
            @error('defaultMinOrderValue')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-4">
            <button type="button" wire:click="save" class="btn btn-primary btn-sm">Speichern</button>
        </div>
    </div>
</div>
