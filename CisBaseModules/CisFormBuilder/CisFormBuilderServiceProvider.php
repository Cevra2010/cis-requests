<?php

namespace CisFoundation\CisFormBuilder;

use Illuminate\Support\ServiceProvider;

class CisFormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('cis.form-builder', fn() => new CisFormBuilder());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'cis-form-builder');

        $this->callAfterResolving('blade.compiler', function ($blade) {
            // Register Blade component: <x-cis-form name="..." :model="$model" />
            $blade->component('cis-form-builder::components.form', 'cis-form');
            $blade->component('cis-form-builder::components.field', 'cis-field');
        });
    }
}
