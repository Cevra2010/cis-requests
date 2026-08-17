<?php

namespace CisFoundation\CisMenuManager;

use CisFoundation\CisMenuManager\Exception\MenuNotFoundException;
use Illuminate\Support\Collection;

/**
 * MenuManager – zentrale Registry aller Menus.
 */
class MenuManager
{
    protected static Collection $menuCollection;

    public static function boot(): void
    {
        if (!isset(self::$menuCollection)) {
            self::$menuCollection = collect();
        }
    }

    /**
     * Registriert ein neues Menu und gibt es zurück.
     */
    public static function registerMenu(string $slug): Menu
    {
        self::boot();
        $menu = new Menu($slug);
        self::$menuCollection->put($slug, $menu);
        return $menu;
    }

    /**
     * Gibt ein registriertes Menu zurück.
     *
     * @throws MenuNotFoundException
     */
    public static function get(string $slug): Menu
    {
        self::boot();

        if (!self::$menuCollection->has($slug)) {
            throw new MenuNotFoundException("Menu \"{$slug}\" nicht gefunden.");
        }

        return self::$menuCollection->get($slug);
    }

    public static function has(string $slug): bool
    {
        self::boot();
        return self::$menuCollection->has($slug);
    }
}
