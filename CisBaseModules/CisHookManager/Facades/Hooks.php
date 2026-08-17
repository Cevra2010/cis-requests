<?php

namespace CisFoundation\CisHookManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string  addFilter(string $hook, callable $callback, int $priority = 10, ?string $module = null)
 * @method static mixed   applyFilter(string $hook, mixed $value, mixed ...$args)
 * @method static void    removeFilter(string $id)
 * @method static string  addAction(string $hook, callable $callback, int $priority = 10, ?string $module = null)
 * @method static void    doAction(string $hook, mixed ...$args)
 * @method static void    removeAction(string $id)
 * @method static void    removeModuleHooks(string $module)
 * @method static array   getModuleHooks(string $module)
 * @method static bool    hasFilter(string $hook)
 * @method static bool    hasAction(string $hook)
 *
 * @see \CisFoundation\CisHookManager\CisHooks
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cis.hooks';
    }
}
