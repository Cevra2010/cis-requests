<div class="max-w-2xl">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-800 mb-1">Firma &amp; Anschrift</h2>
        <p class="text-sm text-gray-500 mb-6">
            Diese Angaben werden u. a. auf Ausschreibungsdokumenten und im Einrichtungs-Assistenten verwendet.
        </p>

        @if(session('success'))
            <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ session('success') }}</div>
        @endif

        <div class="space-y-5">
            <div>
                <label class="cis-label" for="companyName">Firma</label>
                <input type="text" id="companyName" wire:model="companyName" class="cis-input w-full" placeholder="Musterfirma GmbH">
                @error('companyName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-3 gap-5">
                <div class="col-span-2">
                    <label class="cis-label" for="companyStreet">Straße &amp; Hausnummer</label>
                    <input type="text" id="companyStreet" wire:model="companyStreet" class="cis-input w-full">
                    @error('companyStreet')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="companyPostalCode">PLZ</label>
                    <input type="text" id="companyPostalCode" wire:model="companyPostalCode" class="cis-input w-full">
                    @error('companyPostalCode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="cis-label" for="companyCity">Ort</label>
                    <input type="text" id="companyCity" wire:model="companyCity" class="cis-input w-full">
                    @error('companyCity')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="companyCountry">Land</label>
                    <input type="text" id="companyCountry" wire:model="companyCountry" class="cis-input w-full" placeholder="Deutschland">
                    @error('companyCountry')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="cis-label" for="companyVatId">USt-IdNr.</label>
                    <input type="text" id="companyVatId" wire:model="companyVatId" class="cis-input w-full" placeholder="DE123456789">
                    @error('companyVatId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="companyPhone">Telefon</label>
                    <input type="text" id="companyPhone" wire:model="companyPhone" class="cis-input w-full">
                    @error('companyPhone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="cis-label" for="companyEmail">E-Mail</label>
                    <input type="email" id="companyEmail" wire:model="companyEmail" class="cis-input w-full">
                    @error('companyEmail')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="cis-label" for="companyWebsite">Website</label>
                    <input type="text" id="companyWebsite" wire:model="companyWebsite" class="cis-input w-full" placeholder="www.musterfirma.de">
                    @error('companyWebsite')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pt-6 mt-6 border-t border-gray-100">
            <button type="button" wire:click="save" class="btn btn-primary btn-sm">Speichern</button>
        </div>
    </div>
</div>
