<?php
namespace CisFoundation\CisTableBuilder;

use CisFoundation\CisTableBuilder\Component\CisTableComponent;
use CisFoundation\CisTableBuilder\Livewire\CisTableLivewireComponent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class CisTableServiceProvider extends ServiceProvider {

    public function register() {
        //
    }

    public function boot() {
        $this->loadViewsFrom(__DIR__ . '/resources/views','cis-table-builder');
        Blade::component(CisTableComponent::class,'cis-table');
        Livewire::component('cis-table',CisTableLivewireComponent::class);
    }
}
