<?php

namespace Modules\Ausschreibungsvorlagen\Providers;

use CisFoundation\CisMenuManager\Menu;
use Livewire\Livewire;
use Modules\Ausschreibungsvorlagen\Http\Livewire\TemplateManager;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AusschreibungsvorlagenServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Ausschreibungsvorlagen';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'ausschreibungsvorlagen';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('ausschreibungsvorlagen.template-manager', TemplateManager::class);

        Menu::extend('main', function (Menu $menu) {
            $menu->registerEntry('ausschreibungsvorlagen')
                ->setText('Ausschreibungsvorlagen')
                ->setRoute('ausschreibungsvorlagen.index')
                ->setParent('modules')
                ->setPriority(25);
        }, module: 'Ausschreibungsvorlagen');
    }
}
