{{--
    Wiederverwendbare Berechtigungs-Matrix.
    Erwartet: $permissions (Collection groupedByPrefix, Key = lesbares Label)
    Gruppe/Rolle: $granted  (slug → bool)
    Benutzer:     $userPerms (slug → UserPermission)
--}}
<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
@foreach($permissions as $groupLabel => $perms)
<div class="cis-card">
    {{-- Card-Header mit Gruppenname + Badge --}}
    <div class="flex items-center justify-between mb-3 pb-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-800">{{ $groupLabel }}</h3>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
            {{ $perms->count() }}
        </span>
    </div>

    <div class="divide-y divide-gray-50">
        @foreach($perms as $perm)
        <div class="flex items-center justify-between py-2.5 gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900 leading-tight">{{ $perm->label }}</p>
                @if($perm->description)
                    <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ $perm->description }}</p>
                @endif
            </div>

            @if(isset($userPerms))
                {{-- Benutzer: 3 Zustände (Vererbt / Erlaubt / Verboten) --}}
                @php
                    $userPerm = $userPerms->get($perm->slug);
                    $state = $userPerm ? ($userPerm->granted ? 'granted' : 'denied') : 'inherit';
                @endphp
                <div class="flex items-center gap-0.5 shrink-0">
                    <label class="flex items-center gap-1 cursor-pointer px-2 py-1 rounded text-xs font-medium transition-colors
                                  {{ $state === 'inherit' ? 'bg-gray-100 text-gray-700' : 'hover:bg-gray-50 text-gray-400' }}">
                        <input type="radio" name="perm_state[{{ $perm->slug }}]" value="inherit"
                               class="sr-only" {{ $state === 'inherit' ? 'checked' : '' }}
                               onchange="updatePermInput(this)">
                        Vererbt
                    </label>
                    <label class="flex items-center gap-1 cursor-pointer px-2 py-1 rounded text-xs font-medium transition-colors
                                  {{ $state === 'granted' ? 'bg-green-100 text-green-700' : 'hover:bg-gray-50 text-gray-400' }}">
                        <input type="radio" name="perm_state[{{ $perm->slug }}]" value="granted"
                               class="sr-only" {{ $state === 'granted' ? 'checked' : '' }}
                               onchange="updatePermInput(this)">
                        Erlaubt
                    </label>
                    <label class="flex items-center gap-1 cursor-pointer px-2 py-1 rounded text-xs font-medium transition-colors
                                  {{ $state === 'denied' ? 'bg-red-100 text-red-700' : 'hover:bg-gray-50 text-gray-400' }}">
                        <input type="radio" name="perm_state[{{ $perm->slug }}]" value="denied"
                               class="sr-only" {{ $state === 'denied' ? 'checked' : '' }}
                               onchange="updatePermInput(this)">
                        Verboten
                    </label>
                    <input type="hidden" name="granted[]" value="" data-slug="{{ $perm->slug }}" data-type="granted" disabled>
                    <input type="hidden" name="denied[]"  value="" data-slug="{{ $perm->slug }}" data-type="denied"  disabled>
                </div>
            @else
                {{-- Gruppe/Rolle: Toggle --}}
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox"
                           name="permissions[]"
                           value="{{ $perm->slug }}"
                           class="sr-only peer"
                           {{ ($granted->get($perm->slug) ?? false) ? 'checked' : '' }}>
                    <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary-500
                                rounded-full peer peer-checked:bg-primary-600
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all
                                peer-checked:after:translate-x-4"></div>
                </label>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endforeach
</div>

@if(isset($userPerms))
<script>
const permStateColors = {
    inherit: ['bg-gray-100',  'text-gray-700'],
    granted: ['bg-green-100', 'text-green-700'],
    denied:  ['bg-red-100',   'text-red-700'],
};

function updatePermInput(radio) {
    const slug     = radio.name.match(/\[(.+)\]/)[1];
    const value    = radio.value;
    const wrapper  = radio.closest('div');

    // Refresh label colours
    wrapper.querySelectorAll('label').forEach(lbl => {
        const r = lbl.querySelector('input[type="radio"]');
        if (!r) return;
        const active = r.value === value;
        lbl.classList.remove('bg-gray-100','text-gray-700','bg-green-100','text-green-700','bg-red-100','text-red-700','text-gray-400');
        if (active) {
            lbl.classList.add(...permStateColors[r.value]);
        } else {
            lbl.classList.add('text-gray-400');
        }
    });

    const grantedInput = wrapper.querySelector('[data-type="granted"]');
    const deniedInput  = wrapper.querySelector('[data-type="denied"]');

    if (value === 'granted') {
        grantedInput.value = slug; grantedInput.disabled = false;
        deniedInput.value  = '';   deniedInput.disabled  = true;
    } else if (value === 'denied') {
        deniedInput.value  = slug; deniedInput.disabled  = false;
        grantedInput.value = '';   grantedInput.disabled = true;
    } else {
        grantedInput.value = ''; grantedInput.disabled = true;
        deniedInput.value  = ''; deniedInput.disabled  = true;
    }
}

document.querySelectorAll('[name^="perm_state"]').forEach(r => {
    if (r.checked) updatePermInput(r);
});
</script>
@endif
