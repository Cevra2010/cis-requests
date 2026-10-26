<?php

namespace App\Http\Livewire\Setting;

use App\Models\Setting;
use Livewire\Component;

class GeneralSettings extends Component
{
    public string $defaultMinOrderValue = '';

    public string $displayTimezone = '';

    public function mount(): void
    {
        $this->defaultMinOrderValue = (string) Setting::get('default_min_order_value', '0');
        $this->displayTimezone      = (string) config('app.display_timezone');
    }

    public function save(): void
    {
        $this->validate([
            'defaultMinOrderValue' => 'required|numeric|min:0',
            'displayTimezone'      => ['required', 'string', \Illuminate\Validation\Rule::in(\DateTimeZone::listIdentifiers())],
        ], [
            'displayTimezone.in' => 'Bitte eine gültige Zeitzone wählen.',
        ]);

        Setting::set('display_timezone', $this->displayTimezone);
        \App\Services\RuntimeSettings::applyTimezone($this->displayTimezone);

        Setting::set('default_min_order_value', (float) str_replace(',', '.', $this->defaultMinOrderValue));

        session()->flash('success', 'Einstellungen wurden gespeichert.');
    }

    public function render()
    {
        $groups = [];
        foreach (\DateTimeZone::listIdentifiers() as $id) {
            $groups[str_contains($id, '/') ? explode('/', $id, 2)[0] : 'Sonstige'][] = $id;
        }
        // Europa zuerst, dann der Rest alphabetisch.
        uksort($groups, fn ($a, $b) => [$a !== 'Europe', $a] <=> [$b !== 'Europe', $b]);

        return view('livewire.setting.general-settings', [
            'timezoneGroups' => $groups,
            'localNow'       => now()->setTimezone(in_array($this->displayTimezone, \DateTimeZone::listIdentifiers(), true) ? $this->displayTimezone : config('app.display_timezone'))->format('d.m.Y H:i'),
        ]);
    }
}
