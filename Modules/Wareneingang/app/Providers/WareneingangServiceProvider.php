<?php

namespace Modules\Wareneingang\Providers;

use App\Models\Category;
use CisFoundation\CisCategoryManager\CisCategoryManager;
use Livewire\Livewire;
use Modules\Wareneingang\Http\Livewire\GoodsReceiptChecklist;
use Modules\Wareneingang\Http\Livewire\GoodsReceiptManager;
use Nwidart\Modules\Support\ModuleServiceProvider;

class WareneingangServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Wareneingang';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'wareneingang';

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

        Livewire::component('wareneingang.goods-receipt-manager', GoodsReceiptManager::class);
        Livewire::component('wareneingang.goods-receipt-checklist', GoodsReceiptChecklist::class);

        CisCategoryManager::registerType('wareneingang.item_status', 'Wareneingang-Status', module: 'Wareneingang');
        $this->app->booted(fn () => $this->seedDefaultStatusCategories());
    }

    /**
     * Sinnvolle Startwerte für den Folgestatus nach kontrolliertem Wareneingang
     * – über die bestehende Kategorien-Verwaltung frei erweiterbar/änderbar.
     * Läuft idempotent bei jedem Boot (analog zu CisPermissionManager).
     */
    private function seedDefaultStatusCategories(): void
    {
        if (Category::ofType('wareneingang.item_status')->exists()) {
            return;
        }

        foreach (['Eingelagert', 'Versendet an Fahrzeughersteller'] as $i => $name) {
            Category::create([
                'type'       => 'wareneingang.item_status',
                'name'       => $name,
                'sort_order' => $i,
                'module'     => 'Wareneingang',
            ]);
        }
    }
}
