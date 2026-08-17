<?php

namespace Modules\Branding\Providers;

use CisFoundation\CisMenuManager\Menu;
use Livewire\Livewire;
use Modules\Branding\Http\Livewire\BrandingSettings;
use Nwidart\Modules\Support\ModuleServiceProvider;

class BrandingServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Branding';
    protected string $nameLower = 'branding';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Livewire::component('branding.branding-settings', BrandingSettings::class);

        Menu::extend('main', function (Menu $menu) {
            $menu->registerEntry('branding')
                ->setText('Branding')
                ->setRoute('branding.settings')
                ->setParent('modules')
                ->setPriority(10);
        }, module: 'Branding');
    }
}
