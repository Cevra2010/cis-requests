<?php

namespace CisFoundation\CisCategoryManager;

use App\Models\Category;
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
 * Kategorien sind je Typ als frei erweiterbare Baumstruktur organisiert
 * (jede Kategorie kann eine Eltern- und/oder beliebig viele Kind-Kategorien
 * haben). Kategorien verwalten: /Category
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

    /**
     * Alle Kategorien eines Typs, flach, tiefenorientiert sortiert (jede
     * Kategorie direkt gefolgt von ihren Nachfahren). Jede Zeile trägt ein
     * zur Laufzeit gesetztes `depth`-Attribut (0 = Wurzel) für Einrückung.
     */
    public static function forType(string $type): Collection
    {
        $all      = Category::query()->ofType($type)->orderBy('sort_order')->orderBy('name')->get();
        $byParent = $all->groupBy('parent_id');

        return self::flattenByParent($byParent, null, 0);
    }

    private static function flattenByParent(Collection $byParent, $parentId, int $depth): Collection
    {
        $result = collect();
        foreach ($byParent->get($parentId, collect()) as $node) {
            $node->depth = $depth;
            $result->push($node);
            $result = $result->merge(self::flattenByParent($byParent, $node->id, $depth + 1));
        }
        return $result;
    }

    /** Nur die Wurzel-Kategorien eines Typs, mit rekursiv geladenen `children`. */
    public static function treeForType(string $type): Collection
    {
        $all      = Category::query()->ofType($type)->orderBy('sort_order')->orderBy('name')->get();
        $byParent = $all->groupBy('parent_id');

        return self::buildTree($byParent, null);
    }

    private static function buildTree(Collection $byParent, $parentId): Collection
    {
        return $byParent->get($parentId, collect())
            ->map(function (Category $node) use ($byParent) {
                $node->setRelation('children', self::buildTree($byParent, $node->id));
                return $node;
            })
            ->values();
    }

    public static function all(): Collection
    {
        return Category::query()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Für Select-Felder: [id => eingerückter Name] */
    public static function optionsForType(string $type): array
    {
        return self::forType($type)
            ->mapWithKeys(fn (Category $c) => [$c->id => str_repeat('— ', $c->depth) . $c->name])
            ->toArray();
    }
}
