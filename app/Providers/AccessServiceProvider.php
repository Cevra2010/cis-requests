<?php

namespace App\Providers;

use App\Http\Logic\CisAccess\CisAccess;
use App\Http\Logic\CisAccess\Facades\Access;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AccessServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('access', function($app) {
            return new CisAccess();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::if('access',function($area_slug) {
            if(Access::hasAccess($area_slug)) {
                return true;
            }
            return false;
        });
    }
}
