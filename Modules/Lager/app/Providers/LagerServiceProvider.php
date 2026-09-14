<?php

namespace Modules\Lager\Providers;

use CisFoundation\CisMenuManager\Menu;
use Livewire\Livewire;
use Modules\Lager\Http\Livewire\LagerBuchen;
use Modules\Lager\Http\Livewire\LagerortManager;
use Modules\Lager\Http\Livewire\LagerUebersicht;
use Modules\Lager\Http\Livewire\ProjectLagerManager;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LagerServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Lager';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'lager';

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

        Livewire::component('lager.lagerort-manager', LagerortManager::class);
        Livewire::component('lager.lager-uebersicht', LagerUebersicht::class);
        Livewire::component('lager.lager-buchen', LagerBuchen::class);
        Livewire::component('lager.project-lager-manager', ProjectLagerManager::class);

        // Eigener Hauptmenüpunkt (anders als Wareneingang, das nur ein Projekt-Tab
        // ist – die Warenübersicht ist eine eigenständige, häufig genutzte Seite).
        Menu::extend('main', function (Menu $menu) {
            $menu->registerEntry('lager')
                ->setText('Lager')
                ->setRoute('lager.index')
                ->setIcon('warehouse')
                ->setPriority(55);
        }, module: 'Lager');
    }
}
