<?php

namespace Modules\Wareneingang\Providers;

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
    }
}
