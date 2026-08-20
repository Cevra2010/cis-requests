<?php

namespace App\Http\Livewire\Setting;

use App\Models\Setting;
use Livewire\Component;

class CompanySettings extends Component
{
    public string $companyName = '';
    public string $companyStreet = '';
    public string $companyPostalCode = '';
    public string $companyCity = '';
    public string $companyCountry = '';
    public string $companyVatId = '';
    public string $companyPhone = '';
    public string $companyEmail = '';
    public string $companyWebsite = '';

    public function mount(): void
    {
        $this->companyName       = (string) Setting::get('company_name', '');
        $this->companyStreet     = (string) Setting::get('company_street', '');
        $this->companyPostalCode = (string) Setting::get('company_postal_code', '');
        $this->companyCity       = (string) Setting::get('company_city', '');
        $this->companyCountry    = (string) Setting::get('company_country', '');
        $this->companyVatId      = (string) Setting::get('company_vat_id', '');
        $this->companyPhone      = (string) Setting::get('company_phone', '');
        $this->companyEmail      = (string) Setting::get('company_email', '');
        $this->companyWebsite    = (string) Setting::get('company_website', '');
    }

    public function save(): void
    {
        $this->validate([
            'companyName'       => 'nullable|string|max:255',
            'companyStreet'     => 'nullable|string|max:255',
            'companyPostalCode' => 'nullable|string|max:20',
            'companyCity'       => 'nullable|string|max:255',
            'companyCountry'    => 'nullable|string|max:255',
            'companyVatId'      => 'nullable|string|max:50',
            'companyPhone'      => 'nullable|string|max:50',
            'companyEmail'      => 'nullable|email|max:255',
            'companyWebsite'    => 'nullable|string|max:255',
        ]);

        Setting::set('company_name', $this->companyName ?: null);
        Setting::set('company_street', $this->companyStreet ?: null);
        Setting::set('company_postal_code', $this->companyPostalCode ?: null);
        Setting::set('company_city', $this->companyCity ?: null);
        Setting::set('company_country', $this->companyCountry ?: null);
        Setting::set('company_vat_id', $this->companyVatId ?: null);
        Setting::set('company_phone', $this->companyPhone ?: null);
        Setting::set('company_email', $this->companyEmail ?: null);
        Setting::set('company_website', $this->companyWebsite ?: null);

        session()->flash('success', 'Firmendaten wurden gespeichert.');
    }

    public function render()
    {
        return view('livewire.setting.company-settings');
    }
}
