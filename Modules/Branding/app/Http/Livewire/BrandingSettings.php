<?php

namespace Modules\Branding\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Branding\Models\BrandingSetting;

class BrandingSettings extends Component
{
    use WithFileUploads;

    public string  $header_line1 = '';
    public string  $header_line2 = '';
    public string  $footer_left  = '';
    public string  $footer_right = '';
    public string  $accent_color = '#4F46E5';
    public         $logo         = null;
    public ?string $logoUrl      = null;
    public bool    $saved        = false;

    public function mount(): void
    {
        $setting = BrandingSetting::current();

        $this->header_line1 = $setting->header_line1 ?? '';
        $this->header_line2 = $setting->header_line2 ?? '';
        $this->footer_left  = $setting->footer_left  ?? '';
        $this->footer_right = $setting->footer_right ?? '';
        $this->accent_color = $setting->accent_color ?? '#4F46E5';
        $this->logoUrl      = $setting->logoUrl();
    }

    public function save(): void
    {
        $this->validate([
            'header_line1' => 'nullable|string|max:120',
            'header_line2' => 'nullable|string|max:120',
            'footer_left'  => 'nullable|string|max:200',
            'footer_right' => 'nullable|string|max:200',
            'accent_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ], [
            'accent_color.regex' => 'Bitte eine gültige Hex-Farbe eingeben (z.B. #4F46E5).',
            'logo.image'         => 'Nur Bilddateien erlaubt (PNG, JPG, SVG).',
            'logo.max'           => 'Das Logo darf maximal 2 MB groß sein.',
        ]);

        $setting = BrandingSetting::current();

        $data = [
            'header_line1' => $this->header_line1 ?: null,
            'header_line2' => $this->header_line2 ?: null,
            'footer_left'  => $this->footer_left  ?: null,
            'footer_right' => $this->footer_right ?: null,
            'accent_color' => $this->accent_color,
        ];

        if ($this->logo) {
            if ($setting->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = $this->logo->store('branding', 'public');
            $this->logo        = null;
        }

        $setting->update($data);
        $this->logoUrl = $setting->fresh()->logoUrl();
        $this->saved   = true;
    }

    public function removeLogo(): void
    {
        $setting = BrandingSetting::current();

        if ($setting->logo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($setting->logo_path);
            $setting->update(['logo_path' => null]);
        }

        $this->logoUrl = null;
        $this->logo    = null;
    }

    public function render()
    {
        return view('branding::livewire.branding-settings');
    }
}
