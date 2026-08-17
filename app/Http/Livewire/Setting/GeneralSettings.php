<?php

namespace App\Http\Livewire\Setting;

use App\Models\Setting;
use Livewire\Component;

class GeneralSettings extends Component
{
    public string $defaultMinOrderValue = '';

    public function mount(): void
    {
        $this->defaultMinOrderValue = (string) Setting::get('default_min_order_value', '0');
    }

    public function save(): void
    {
        $this->validate([
            'defaultMinOrderValue' => 'required|numeric|min:0',
        ]);

        Setting::set('default_min_order_value', (float) str_replace(',', '.', $this->defaultMinOrderValue));

        session()->flash('success', 'Einstellungen wurden gespeichert.');
    }

    public function render()
    {
        return view('livewire.setting.general-settings');
    }
}
