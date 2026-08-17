<?php

namespace CisFoundation\CisHookManager;

/**
 * CisHooks – zentraler Filter- und Action-Bus für die gesamte Anwendung.
 *
 * Filter: modifizieren einen Wert und geben ihn zurück (transform/extend).
 * Actions: führen Seiteneffekte aus, ohne einen Wert zurückzugeben.
 *
 * Module registrieren ihre Hooks mit ->forModule('slug') so dass sie beim
 * Deinstallieren sauber entfernt werden können.
 *
 * Verwendung:
 *   // Registrieren (im ServiceProvider des Moduls):
 *   CisHooks::addFilter('product.table.columns', fn($cols) => $cols->push(...), priority: 10, module: 'MinQualityModule');
 *   CisHooks::addAction('product.saved', fn($product) => ..., module: 'MinQualityModule');
 *
 *   // Auslösen (im Basisystem):
 *   $columns = CisHooks::applyFilter('product.table.columns', $columns);
 *   CisHooks::doAction('product.saved', $product);
 *
 *   // Modul deinstallieren:
 *   CisHooks::removeModuleHooks('MinQualityModule');
 */
class CisHooks
{
    /** @var array<string, array<int, array<array{id:string, callback:callable, module:string|null}>>> */
    protected static array $filters = [];

    /** @var array<string, array<int, array<array{id:string, callback:callable, module:string|null}>>> */
    protected static array $actions = [];

    /** @var array<string, list<array{type:string, hook:string, priority:int, id:string}>> */
    protected static array $moduleRegistry = [];

    // ────────────────────────────────────────────────────────────────────────
    // Filter API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Registriert einen Filter-Callback für den gegebenen Hook.
     * Gibt eine ID zurück mit der der Hook später einzeln entfernt werden kann.
     */
    public static function addFilter(
        string   $hook,
        callable $callback,
        int      $priority = 10,
        ?string  $module   = null
    ): string {
        $id = self::generateId();
        self::$filters[$hook][$priority][] = compact('id', 'callback', 'module');

        if ($module !== null) {
            self::$moduleRegistry[$module][] = [
                'type'     => 'filter',
                'hook'     => $hook,
                'priority' => $priority,
                'id'       => $id,
            ];
        }

        return $id;
    }

    /**
     * Wendet alle registrierten Filter auf den Wert an und gibt das Ergebnis zurück.
     * Zusätzliche Argumente werden an jeden Callback weitergegeben (aber nicht transformiert).
     */
    public static function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (empty(self::$filters[$hook])) {
            return $value;
        }

        ksort(self::$filters[$hook]);

        foreach (self::$filters[$hook] as $callbacks) {
            foreach ($callbacks as $entry) {
                $value = ($entry['callback'])($value, ...$args);
            }
        }

        return $value;
    }

    /**
     * Entfernt einen einzelnen Filter anhand seiner ID.
     */
    public static function removeFilter(string $id): void
    {
        self::removeById('filters', $id);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Action API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Registriert einen Action-Callback für den gegebenen Hook.
     */
    public static function addAction(
        string   $hook,
        callable $callback,
        int      $priority = 10,
        ?string  $module   = null
    ): string {
        $id = self::generateId();
        self::$actions[$hook][$priority][] = compact('id', 'callback', 'module');

        if ($module !== null) {
            self::$moduleRegistry[$module][] = [
                'type'     => 'action',
                'hook'     => $hook,
                'priority' => $priority,
                'id'       => $id,
            ];
        }

        return $id;
    }

    /**
     * Führt alle registrierten Actions für den Hook aus.
     */
    public static function doAction(string $hook, mixed ...$args): void
    {
        if (empty(self::$actions[$hook])) {
            return;
        }

        ksort(self::$actions[$hook]);

        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $entry) {
                ($entry['callback'])(...$args);
            }
        }
    }

    /**
     * Entfernt einen einzelnen Action-Hook anhand seiner ID.
     */
    public static function removeAction(string $id): void
    {
        self::removeById('actions', $id);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Modul-Management
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Entfernt alle Filter und Actions die ein bestimmtes Modul registriert hat.
     * Wird beim Deinstallieren eines Moduls aufgerufen.
     */
    public static function removeModuleHooks(string $module): void
    {
        if (empty(self::$moduleRegistry[$module])) {
            return;
        }

        foreach (self::$moduleRegistry[$module] as $registration) {
            $store = $registration['type'] === 'filter' ? 'filters' : 'actions';
            self::removeById($store, $registration['id']);
        }

        unset(self::$moduleRegistry[$module]);
    }

    /**
     * Gibt alle Hook-Registrierungen eines Moduls zurück (für Debugging/Logging).
     */
    public static function getModuleHooks(string $module): array
    {
        return self::$moduleRegistry[$module] ?? [];
    }

    /**
     * Gibt zurück ob für einen Hook Filter/Actions registriert sind.
     */
    public static function hasFilter(string $hook): bool
    {
        return !empty(self::$filters[$hook]);
    }

    public static function hasAction(string $hook): bool
    {
        return !empty(self::$actions[$hook]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Interne Hilfsmethoden
    // ────────────────────────────────────────────────────────────────────────

    private static function generateId(): string
    {
        return 'cis_hook_' . uniqid();
    }

    private static function removeById(string $store, string $id): void
    {
        // $store ist 'filters' oder 'actions'
        $ref = $store === 'filters' ? self::$filters : self::$actions;

        foreach ($ref as $hook => $priorities) {
            foreach ($priorities as $priority => $callbacks) {
                $filtered = array_values(array_filter($callbacks, fn($e) => $e['id'] !== $id));

                if ($store === 'filters') {
                    if (empty($filtered)) {
                        unset(self::$filters[$hook][$priority]);
                    } else {
                        self::$filters[$hook][$priority] = $filtered;
                    }
                } else {
                    if (empty($filtered)) {
                        unset(self::$actions[$hook][$priority]);
                    } else {
                        self::$actions[$hook][$priority] = $filtered;
                    }
                }
            }
        }
    }
}
