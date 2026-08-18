<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class GenerateModuleLicense extends Command
{
    protected $signature = 'module:generate-license
                            {module    : Name des Moduls (z.B. Branding)}
                            {master_id : Master-ID der Firma/Installation (siehe license:generate-master)}
                            {licensee  : Name des Lizenznehmers}
                            {--expires= : Ablaufdatum (YYYY-MM-DD), leer = unbegrenzt}';

    protected $description = 'Generiert einen signierten Modul-Lizenzschlüssel, gebunden an eine Master-ID';

    public function handle(LicenseService $licenseService): void
    {
        $module   = $this->argument('module');
        $masterId = $this->argument('master_id');
        $licensee = $this->argument('licensee');
        $expires  = $this->option('expires') ?: null;

        $key = $licenseService->generateKey($module, $masterId, $licensee, $expires);

        $this->newLine();
        $this->line("  <fg=green>Modul:</>        {$module}");
        $this->line("  <fg=green>Master-ID:</>    {$masterId}");
        $this->line("  <fg=green>Lizenznehmer:</> {$licensee}");
        $this->line("  <fg=green>Gültig bis:</>   " . ($expires ?? 'unbegrenzt'));
        $this->newLine();
        $this->line('  <fg=yellow>Lizenzschlüssel:</>');
        $this->newLine();
        $this->line("  <fg=white;options=bold>{$key}</>");
        $this->newLine();
        $this->line('  <fg=gray>Gültig nur für die Installation mit dieser Master-ID.</>');
        $this->newLine();
    }
}
