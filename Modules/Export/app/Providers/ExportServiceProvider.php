<?php

namespace Modules\Export\Providers;

use CisFoundation\CisMenuManager\Menu;
use Livewire\Livewire;
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

        Menu::extend('main', function (Menu $menu) {
            $menu->registerEntry('export')
                ->setText('Export-Vorlagen')
                ->setRoute('export.templates')
                ->setParent('modules')
                ->setPriority(20);
        }, module: 'Export');
    }
}
