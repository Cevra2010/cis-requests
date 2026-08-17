<?php

namespace CisFoundation\CisCategoryManager;

use Illuminate\Support\Collection;

/**
 * CisCategoryManager – Registry für Kategorie-Typen + Zugriff auf Kategorien.
 *
 * Module registrieren ihren Kategorie-Typ im ServiceProvider:
 *
 *   CisCategoryManager::registerType(
 *       type:   'project.category',
 *       label:  'Projektkategorie',
 *       module: 'Core'
 *   );
 *
 * Kategorien verwalten: /Category (CRUD)
 *
 * Im Blade-Template:
 *   <x-cis-category-select type="project.category" name="category_id" :value="$project->category_id" />
 */
class CisCategoryManager
{
    /** Registrierte Typen: ['project.category' => ['label' => ..., 'module' => ...]] */
    protected static array $types = [];

    // ────────────────────────────────────────────────────────────────────────
    // Typ-Registry
    // ────────────────────────────────────────────────────────────────────────

    public static function registerType(string $type, string $label, ?string $module = null): void
    {
        self::$types[$type] = ['label' => $label, 'module' => $module];
    }

    public static function getTypes(): array
    {
        return self::$types;
    }

    public static function getTypeLabel(string $type): string
    {
        return self::$types[$type]['label'] ?? $type;
    }

    public static function hasType(string $type): bool
    {
        return isset(self::$types[$type]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Kategorien abrufen
    // ────────────────────────────────────────────────────────────────────────

    public static function forType(string $type): Collection
    {
        return \DB::table('categories')
            ->where('type', $type)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public static function all(): Collection
    {
        return \DB::table('categories')
            ->whereNull('deleted_at')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Für Select-Felder: [id => name] */
    public static function optionsForType(string $type): array
    {
        return self::forType($type)
            ->pluck('name', 'id')
            ->toArray();
    }
}
