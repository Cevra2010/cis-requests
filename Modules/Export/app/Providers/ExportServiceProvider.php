<?php

namespace Modules\Export\Providers;

use CisFoundation\CisMenuManager\Menu;
use Livewire\Livewire;
use CisFoundation\CisPermissionManager\CisPermissionManager;
use Modules\Export\Http\Livewire\DataTransfer;
use Modules\Export\Http\Livewire\TemplateManager;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ExportServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Export';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'export';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Livewire::component('export.template-manager', TemplateManager::class);
        Livewire::component('export.data-transfer', DataTransfer::class);

        CisPermissionManager::registerGroup('export', 'Export-Import');
        CisPermissionManager::register(
            'export.transfer',
            'Import-/Export-Werkzeug nutzen',
            description: 'Erlaubt das Exportieren und Importieren von Systemdaten (z.B. Produkte und Preise) über das Import-/Export-Werkzeug.'
        );

        Menu::extend('main', function (Menu $menu) {
            $menu->registerEntry('export')
                ->setText('Export-Import')
                ->setRoute('export.templates')
                ->setParent('modules')
                ->setPriority(20);

            $menu->registerEntry('export-transfer')
                ->setText('Import / Export')
                ->setRoute('export.transfer')
                ->setParent('modules')
                ->setPriority(21)
                ->setVisible(fn () => auth()->user()?->hasPermission('export.transfer') ?? false);
        }, module: 'Export');
    }
}
