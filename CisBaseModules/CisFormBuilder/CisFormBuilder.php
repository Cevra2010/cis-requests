<?php

namespace CisFoundation\CisFormBuilder;

use CisFoundation\CisFormBuilder\Exception\FormNotFoundException;
use CisFoundation\CisHookManager\CisHooks;
use Illuminate\Support\Collection;

/**
 * CisFormBuilder – zentrale Registry aller Formulare.
 *
 * Basis-Modul definiert ein Formular:
 *   CisFormBuilder::define('product.edit', function(CisForm $form) {
 *       $form->columns(2)
 *           ->addField(CisField::make('name', 'Name')->text()->required())
 *           ->addField(CisField::make('description', 'Beschreibung')->textarea()->fullWidth());
 *   });
 *
 * Feature-Modul erweitert das Formular:
 *   CisFormBuilder::extend('product.edit', function(CisForm $form) {
 *       $form->addField(
 *           CisField::make('min_quality_id', 'Mindestqualität')
 *               ->select()
 *               ->options(MinQuality::all())
 *               ->after('name')
 *       );
 *   }, module: 'MinQualityModule');
 *
 * Modul deinstallieren:
 *   CisFormBuilder::removeModuleExtensions('MinQualityModule');
 */
class CisFormBuilder
{
    protected static Collection $forms;

    public static function boot(): void
    {
        if (!isset(self::$forms)) {
            self::$forms = collect();
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Registry
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Definiert ein neues Formular.
     *
     * @param callable(CisForm): void $callback
     */
    public static function define(string $name, callable $callback): CisForm
    {
        self::boot();

        $form = new CisForm($name);
        $callback($form);
        self::$forms->put($name, $form);

        return $form;
    }

    /**
     * Gibt ein registriertes Formular zurück.
     *
     * @throws FormNotFoundException
     */
    public static function get(string $name): CisForm
    {
        self::boot();

        if (!self::$forms->has($name)) {
            throw new FormNotFoundException("Formular \"{$name}\" nicht gefunden.");
        }

        return self::$forms->get($name);
    }

    public static function has(string $name): bool
    {
        self::boot();
        return self::$forms->has($name);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Module extension
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Modul-sicherer Erweiterungs-Einstiegspunkt.
     *
     * @param callable(CisForm): void $callback
     */
    public static function extend(string $name, callable $callback, ?string $module = null): void
    {
        CisForm::extend($name, $callback, $module);
    }

    /**
     * Entfernt alle Formular-Erweiterungen eines Moduls.
     * Wird beim Deinstallieren des Moduls aufgerufen.
     */
    public static function removeModuleExtensions(string $module): void
    {
        CisHooks::removeModuleHooks($module);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    public static function all(): Collection
    {
        self::boot();
        return self::$forms;
    }
}
