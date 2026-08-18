<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class GenerateMasterLicense extends Command
{
    protected $signature = 'license:generate-master
                            {licensee   : Name des Kunden/der Firma}
                            {--expires= : Ablaufdatum (YYYY-MM-DD), leer = unbegrenzt}';

    protected $description = 'Erzeugt eine neue Firmen-Masterlizenz (eigene Master-ID) für einen Kunden';

    public function handle(LicenseService $licenseService): void
    {
        $licensee = $this->argument('licensee');
        $expires  = $this->option('expires') ?: null;

        ['master_id' => $masterId, 'key' => $key] = $licenseService->generateMasterKey($licensee, $expires);

        $this->newLine();
        $this->line("  <fg=green>Lizenznehmer:</> {$licensee}");
        $this->line("  <fg=green>Gültig bis:</>   " . ($expires ?? 'unbegrenzt'));
        $this->newLine();
        $this->line('  <fg=yellow>Master-ID</> (für spätere Modul-Lizenzen dieses Kunden aufbewahren):');
        $this->newLine();
        $this->line("  <fg=white;options=bold>{$masterId}</>");
        $this->newLine();
        $this->line('  <fg=yellow>Firmenlizenzschlüssel</> (wird vom Kunden im Einrichtungs-Assistenten hinterlegt):');
        $this->newLine();
        $this->line("  <fg=white;options=bold>{$key}</>");
        $this->newLine();
        $this->line('  <fg=gray>Alle Modul-Lizenzen für diesen Kunden müssen mit dieser Master-ID erzeugt werden</>');
        $this->line('  <fg=gray>(module:generate-license {module} ' . $masterId . ' {licensee}) — sonst sind sie ungültig.</>');
        $this->newLine();
    }
}
