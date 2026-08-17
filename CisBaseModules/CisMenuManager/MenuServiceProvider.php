<?php

namespace CisFoundation\CisMenuManager;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use CisFoundation\CisMenuManager\MenuManager;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('cis.menu-manager', fn() => new MenuManager());
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views/', 'cis-menu');
        Blade::component(MenuComponent::class,'cis-menu');
    }
}
