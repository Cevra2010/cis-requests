<div class="max-w-xl space-y-5">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-800 mb-1">E-Mail-Versand (SMTP)</h2>
        <p class="text-sm text-gray-500 mb-4">
            Über diesen Server verschickt CIS Requests E-Mails, z.B. die Workflow-Maßnahme „E-Mail senden“.
            @if($usingSettings)
                <span class="text-emerald-600"><i class="fa fa-circle-check"></i> Es gelten die hier gespeicherten Einstellungen.</span>
            @else
                <span class="text-amber-600"><i class="fa fa-triangle-exclamation"></i> Noch nichts gespeichert – es gelten die Standardwerte der Installation ({{ $envHost }}).</span>
            @endif
        </p>

        @if($saved)
            <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm">Einstellungen wurden gespeichert.</div>
        @endif

        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="cis-label" for="mailHost">SMTP-Server</label>
                <input type="text" id="mailHost" wire:model="host" class="cis-input w-full" placeholder="smtp.example.com">
                @error('host')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="cis-label" for="mailPort">Port</label>
                <input type="text" id="mailPort" wire:model="port" class="cis-input w-full" inputmode="numeric">
                @error('port')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-4">
            <label class="cis-label" for="mailEnc">Verschlüsselung</label>
            <select id="mailEnc" wire:model="encryption" class="cis-input w-full">
                @foreach($encryptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <label class="cis-label" for="mailUser">Benutzername</label>
                <input type="text" id="mailUser" wire:model="username" class="cis-input w-full" autocomplete="off">
            </div>
            <div>
                <label class="cis-label" for="mailPass">Passwort</label>
                <input type="password" id="mailPass" wire:model="password" class="cis-input w-full" autocomplete="new-password"
                       placeholder="{{ $hasPassword ? '•••••••• (unverändert)' : '' }}">
                <p class="mt-1 text-[11px] text-gray-400">Wird verschlüsselt gespeichert und nie angezeigt. Leer lassen, um es nicht zu ändern.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <label class="cis-label" for="mailFrom">Absenderadresse</label>
                <input type="text" id="mailFrom" wire:model="fromAddress" class="cis-input w-full" placeholder="cis@example.com">
                @error('fromAddress')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="cis-label" for="mailFromName">Absendername</label>
                <input type="text" id="mailFromName" wire:model="fromName" class="cis-input w-full" placeholder="CIS Requests">
            </div>
        </div>

        <div class="pt-6 mt-6 border-t border-gray-100 flex items-center gap-3">
            <button type="button" wire:click="save" class="btn btn-primary btn-sm">Speichern</button>
            @if($usingSettings)
                <button type="button" wire:click="resetToDefaults" wire:confirm="Gespeicherte E-Mail-Einstellungen verwerfen und wieder die Standardwerte der Installation verwenden?"
                        class="text-xs text-gray-400 hover:text-red-500">Auf Standardwerte zurücksetzen</button>
            @endif
        </div>
    </div>

    <div class="cis-card">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Verbindung testen</h3>
        <p class="text-xs text-gray-500 mb-3">Sendet eine Test-E-Mail mit den oben eingetragenen Werten – auch ohne vorher zu speichern.</p>
        <div class="flex items-center gap-2">
            <input type="text" wire:model="testRecipient" class="cis-input flex-1" placeholder="Empfänger">
            <button type="button" wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest" class="btn btn-ghost btn-sm shrink-0">
                <i class="fa fa-paper-plane mr-1.5"></i><span wire:loading.remove wire:target="sendTest">Test senden</span><span wire:loading wire:target="sendTest">Sende …</span>
            </button>
        </div>
        @error('testRecipient')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @if($testResult)
            <p class="mt-3 text-sm {{ $testOk ? 'text-emerald-600' : 'text-red-600' }}">
                <i class="fa {{ $testOk ? 'fa-circle-check' : 'fa-circle-xmark' }} mr-1"></i>{{ $testResult }}
            </p>
        @endif
    </div>
</div>
