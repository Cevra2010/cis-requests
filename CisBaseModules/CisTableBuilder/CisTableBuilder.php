<?php

namespace CisFoundation\CisTableBuilder;

use CisFoundation\CisHookManager\CisHooks;
use CisFoundation\CisTableBuilder\Exception\TableNotFoundException;
use Illuminate\Support\Collection;

/**
 * CisTableBuilder – Zentrale Registry für Tabellen-Definitionen.
 *
 * Basis-Nutzung (im ServiceProvider oder Controller):
 *   CisTableBuilder::define('product.list')
 *       ->addColumn(CisColumn::make('name', 'Name')->sortable())
 *       ->addColumn(CisColumn::make('price', 'Preis')->render(fn($r) => $r->priceForHumans()))
 *       ->setData(Product::class)
 *       ->withSearch(['name'])
 *       ->withPagination(20);
 *
 * Erweiterung durch ein Modul:
 *   CisTableBuilder::extend('product.list', function(CisTable $table) {
 *       $table->addColumn(
 *           CisColumn::make('min_quality', 'Mindestqualität')
 *               ->render(fn($row) => $row->minQuality?->label ?? '–')
 *               ->after('name')
 *       );
 *   }, module: 'MinQualityModule');
 */
class CisTableBuilder
{
    /** @var Collection<string, CisTable> */
    protected static Collection $tables;

    /** @var array<string, list<array{callback: callable, module: string|null}>> */
    protected static array $extenders = [];

    // ────────────────────────────────────────────────────────────────────────
    // Tabellen-Definition
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Definiert eine neue Tabelle und gibt das CisTable-Objekt zurück.
     * Bestehende Definition wird überschrieben (idempotent bei mehrfachem boot).
     */
    public static function define(string $name): CisTable
    {
        self::boot();
        $table = new CisTable($name);
        self::$tables->put($name, $table);
        return $table;
    }

    /**
     * Gibt eine definierte Tabelle zurück.
     *
     * @throws TableNotFoundException
     */
    public static function get(string $name): CisTable
    {
        self::boot();

        if (!self::$tables->has($name)) {
            throw new TableNotFoundException("Tabelle \"{$name}\" wurde nicht definiert.");
        }

        return self::$tables->get($name);
    }

    /**
     * Gibt zurück ob eine Tabelle definiert ist.
     */
    public static function has(string $name): bool
    {
        self::boot();
        return self::$tables->has($name);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Erweiterung durch Module
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Registriert eine Erweiterung für eine Tabelle.
     * Die Callback-Closure erhält das CisTable-Objekt und kann es modifizieren.
     *
     * Falls die Tabelle noch nicht definiert ist, wird die Erweiterung gespeichert
     * und beim ersten get()-Aufruf angewandt.
     *
     * @param callable(CisTable): void $callback
     */
    public static function extend(string $tableName, callable $callback, ?string $module = null): void
    {
        self::boot();

        // Direkt anwenden wenn Tabelle bereits definiert
        if (self::$tables->has($tableName)) {
            $callback(self::$tables->get($tableName));
        }

        // Immer speichern (für spätere Definitionen und Modul-Cleanup)
        self::$extenders[$tableName][] = compact('callback', 'module');

        // Via Hook registrieren damit die Erweiterung auch nach Tabellen-Neudefinition greift
        CisHooks::addFilter(
            "table.{$tableName}.columns",
            function (Collection $columns) use ($tableName, $callback): Collection {
                // Erweiterung auf eine temporäre Tabelle anwenden um die Columns zu extrahieren
                $temp = clone (self::$tables->get($tableName) ?? new CisTable($tableName));
                $callback($temp);
                // Neue Columns aus der Erweiterung anhängen
                $temp->getColumns()->each(fn($col) => $columns->push($col));
                return $columns;
            },
            module: $module
        );
    }

    /**
     * Entfernt alle Erweiterungen eines Moduls.
     * Wird beim Deinstallieren des Moduls aufgerufen.
     */
    public static function removeModuleExtensions(string $module): void
    {
        foreach (self::$extenders as $tableName => $extenders) {
            self::$extenders[$tableName] = array_filter(
                $extenders,
                fn($e) => $e['module'] !== $module
            );
        }
        CisHooks::removeModuleHooks($module);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Intern
    // ────────────────────────────────────────────────────────────────────────

    private static function boot(): void
    {
        if (!isset(self::$tables)) {
            self::$tables = collect();
        }
    }
}
