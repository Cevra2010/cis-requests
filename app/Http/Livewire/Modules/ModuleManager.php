<?php

namespace App\Http\Livewire\Modules;

use App\Models\ModuleLicense;
use App\Services\LicenseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\WithFileUploads;
use Nwidart\Modules\Facades\Module;
use ZipArchive;

class ModuleManager extends Component
{
    use WithFileUploads;

    // ── Install Modal ─────────────────────────────────────────────────────────
    public bool   $showInstallModal = false;
    public string $installTab       = 'upload';
    public        $zipFile          = null;
    public string $composerPackage  = '';
    public ?string $composerOutput  = null;

    // ── License Modal ─────────────────────────────────────────────────────────
    public bool    $showLicenseModal = false;
    public string  $licenseModule    = '';
    public string  $licenseKey       = '';
    public ?array  $licensePreview   = null;
    public ?string $licenseError     = null;

    // ── Core modules (CisBaseModules — always active, not removable) ──────────
    protected array $coreModuleDefinitions = [
        'CisMenuManager'       => ['description' => 'Zentrale Menü-Registry für alle Navigationseinträge.',   'icon' => 'fa-bars'],
        'CisFormBuilder'       => ['description' => 'Basismodul für dynamische Formular-Generierung.',         'icon' => 'fa-file-lines'],
        'CisTableBuilder'      => ['description' => 'Basismodul für dynamische Tabellen-Generierung.',         'icon' => 'fa-table'],
        'CisHookManager'       => ['description' => 'Hook- und Event-System für Modul-Erweiterungen.',        'icon' => 'fa-plug'],
        'CisPermissionManager' => ['description' => 'Rollenbasiertes Berechtigungssystem.',                   'icon' => 'fa-shield-halved'],
        'CisCategoryManager'   => ['description' => 'Kategorisierungssystem für Entitäten.',                  'icon' => 'fa-tags'],
    ];

    // ── Enable / Disable ──────────────────────────────────────────────────────

    public function toggle(string $name): void
    {
        $module = Module::findOrFail($name);

        if ($module->get('core', false)) {
            return;
        }

        if ($module->isEnabled()) {
            $module->disable();
            return;
        }

        // Activation: check if license is required
        if ($module->get('requires_license', false)) {
            $licensed = app(LicenseService::class)->isLicensed($name);

            if (!$licensed) {
                $this->openLicenseModal($name);
                return;
            }
        }

        $module->enable();
    }

    // ── License Modal ─────────────────────────────────────────────────────────

    public function openLicenseModal(string $moduleName): void
    {
        $this->licenseModule  = $moduleName;
        $this->licenseKey     = '';
        $this->licensePreview = null;
        $this->licenseError   = null;
        $this->showLicenseModal = true;
    }

    public function previewLicense(): void
    {
        $this->licenseError   = null;
        $this->licensePreview = null;

        if (blank($this->licenseKey)) {
            $this->licenseError = 'Bitte gib einen Lizenzschlüssel ein.';
            return;
        }

        $result = app(LicenseService::class)->validate($this->licenseModule, $this->licenseKey);

        if (!$result['valid']) {
            $this->licenseError = $result['error'];
            return;
        }

        $this->licensePreview = $result;
    }

    public function activateLicense(): void
    {
        $this->licenseError = null;

        $result = app(LicenseService::class)->activate($this->licenseModule, $this->licenseKey);

        if (!$result['valid']) {
            $this->licenseError = $result['error'];
            return;
        }

        // Enable the module now that it's licensed
        Module::findOrFail($this->licenseModule)->enable();

        $this->showLicenseModal = false;
        $this->licenseKey       = '';
        $this->licensePreview   = null;
    }

    public function removeLicense(string $moduleName): void
    {
        ModuleLicense::where('module_name', $moduleName)->delete();
        Module::findOrFail($moduleName)->disable();
    }

    // ── Delete module ─────────────────────────────────────────────────────────

    public function delete(string $name): void
    {
        $module = Module::findOrFail($name);

        if ($module->get('core', false)) {
            return;
        }

        File::deleteDirectory($module->getPath());

        ModuleLicense::where('module_name', $name)->delete();

        $statusFile = base_path('modules_statuses.json');
        if (File::exists($statusFile)) {
            $statuses = json_decode(File::get($statusFile), true) ?? [];
            unset($statuses[$name]);
            File::put($statusFile, json_encode($statuses, JSON_PRETTY_PRINT));
        }
    }

    // ── Install via ZIP ───────────────────────────────────────────────────────

    public function installZip(): void
    {
        $this->validate([
            'zipFile' => 'required|file|mimes:zip|max:51200',
        ], [
            'zipFile.required' => 'Bitte wähle eine ZIP-Datei aus.',
            'zipFile.mimes'    => 'Nur ZIP-Dateien sind erlaubt.',
            'zipFile.max'      => 'Die Datei darf maximal 50 MB groß sein.',
        ]);

        $zip = new ZipArchive();

        if ($zip->open($this->zipFile->getRealPath()) !== true) {
            $this->addError('zipFile', 'Die ZIP-Datei konnte nicht geöffnet werden.');
            return;
        }

        $moduleName = $this->detectModuleName($zip);

        if (!$moduleName) {
            $zip->close();
            $this->addError('zipFile', 'Kein gültiges Modul-Paket: module.json nicht gefunden.');
            return;
        }

        $zip->extractTo(base_path('Modules/' . $moduleName));
        $zip->close();

        $this->showInstallModal = false;
        $this->zipFile          = null;
    }

    // ── Install via Composer ──────────────────────────────────────────────────

    public function installComposer(): void
    {
        $package = trim($this->composerPackage);

        if (empty($package) || !preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/i', $package)) {
            $this->addError('composerPackage', 'Ungültiger Paketname (Format: vendor/package).');
            return;
        }

        $escaped             = escapeshellarg($package);
        $this->composerOutput = shell_exec(
            'cd ' . escapeshellarg(base_path()) . " && composer require {$escaped} 2>&1"
        );
    }

    // ── Data ──────────────────────────────────────────────────────────────────

    private function installedModules(): Collection
    {
        $licenseService = app(LicenseService::class);

        return collect(Module::all())->map(function ($module) use ($licenseService) {
            $requiresLicense = (bool) $module->get('requires_license', false);
            $license         = $requiresLicense ? $licenseService->forModule($module->getName()) : null;

            return [
                'name'            => $module->getName(),
                'alias'           => $module->get('alias', $module->getLowerName()),
                'description'     => $module->getDescription(),
                'version'         => $module->get('version', '—'),
                'enabled'         => $module->isEnabled(),
                'core'            => (bool) $module->get('core', false),
                'requires_license'=> $requiresLicense,
                'license'         => $license,
                'path'            => $module->getPath(),
            ];
        })->values();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function detectModuleName(ZipArchive $zip): ?string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_ends_with($zip->getNameIndex($i), 'module.json')) {
                $data = json_decode($zip->getFromIndex($i), true);
                return $data['name'] ?? null;
            }
        }
        return null;
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $licenseService = app(LicenseService::class);

        return view('livewire.modules.module-manager', [
            'coreModules'         => $this->coreModuleDefinitions,
            'installedModules'    => $this->installedModules(),
            'hasMasterLicense'    => $licenseService->hasMasterLicense(),
            'masterLicensee'      => $licenseService->installationLicensee(),
        ]);
    }
}
