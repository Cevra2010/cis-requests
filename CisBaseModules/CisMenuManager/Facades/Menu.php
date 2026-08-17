<?php

namespace CisFoundation\CisMenuManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CisFoundation\CisMenuManager\Menu registerMenu(string $slug)
 * @method static \CisFoundation\CisMenuManager\Menu get(string $slug)
 * @method static bool has(string $slug)
 */
class Menu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cis.menu-manager';
    }
}
