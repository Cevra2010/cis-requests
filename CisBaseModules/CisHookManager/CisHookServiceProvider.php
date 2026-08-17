<?php

namespace CisFoundation\CisHookManager;

use Illuminate\Support\ServiceProvider;

class CisHookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton damit alle Aufrufer dieselbe statische Instanz nutzen
        $this->app->singleton('cis.hooks', fn() => new CisHooks());
    }

    public function boot(): void
    {
        //
    }
}
