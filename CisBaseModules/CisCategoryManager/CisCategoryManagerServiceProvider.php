<?php

namespace CisFoundation\CisCategoryManager;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CisCategoryManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('cis.categories', fn() => new CisCategoryManager());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'cis-category');

        // Blade-Komponente: <x-cis-category-select ... />
        Blade::component('cis-category::components.category-select', 'cis-category-select');

        // Core-Typen registrieren
        CisCategoryManager::registerType('project.category', 'Projektkategorie', module: 'Core');
    }
}
