<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class GenerateModuleLicense extends Command
{
    protected $signature = 'module:generate-license
                            {module    : Name des Moduls (z.B. Branding)}
                            {licensee  : Name des Lizenznehmers}
                            {--expires= : Ablaufdatum (YYYY-MM-DD), leer = unbegrenzt}';

    protected $description = 'Generiert einen signierten Lizenzschlüssel für ein Modul';

    public function handle(LicenseService $licenseService): void
    {
        $module   = $this->argument('module');
        $licensee = $this->argument('licensee');
        $expires  = $this->option('expires') ?: null;

        $key = $licenseService->generateKey($module, $licensee, $expires);

        $this->newLine();
        $this->line("  <fg=green>Modul:</>     {$module}");
        $this->line("  <fg=green>Lizenznehmer:</> {$licensee}");
        $this->line("  <fg=green>Gültig bis:</> " . ($expires ?? 'unbegrenzt'));
        $this->newLine();
        $this->line('  <fg=yellow>Lizenzschlüssel:</>');
        $this->newLine();
        $this->line("  <fg=white;options=bold>{$key}</>");
        $this->newLine();
        $this->line('  <fg=gray>Dieser Schlüssel ist signiert und kann nicht verändert werden.</>');
        $this->newLine();
    }
}
