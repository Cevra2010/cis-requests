<?php

namespace App\Http\Livewire\Setting;

use App\Services\LicenseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LicenseSettings extends Component
{
    public string $licenseKey = '';

    public bool $editing = false;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasPermission('system.reset'), 403);
    }

    public function edit(): void
    {
        $this->editing = true;
        $this->licenseKey = '';
        $this->resetErrorBag('licenseKey');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->licenseKey = '';
    }

    public function activate(): void
    {
        $this->validate(['licenseKey' => 'required|string']);

        $result = app(LicenseService::class)->activateMaster(trim($this->licenseKey));

        if (! $result['valid']) {
            $this->addError('licenseKey', $result['error']);
            return;
        }

        $this->editing = false;
        $this->licenseKey = '';
        session()->flash('success', 'Firmenlizenz wurde aktiviert.');
    }

    public function render()
    {
        $service = app(LicenseService::class);

        return view('livewire.setting.license-settings', [
            'hasMasterLicense' => $service->hasMasterLicense(),
            'licensee'         => $service->installationLicensee(),
            'masterId'         => $service->installationMasterId(),
            'expiresAt'        => \App\Models\Setting::get('license_master_expires_at'),
        ]);
    }
}
