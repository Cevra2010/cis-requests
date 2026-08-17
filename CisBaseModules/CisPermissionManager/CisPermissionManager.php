<?php

namespace CisFoundation\CisPermissionManager;

use App\Models\Permission;
use Illuminate\Support\Collection;

/**
 * CisPermissionManager – zentrale Registry aller Berechtigungen.
 *
 * Module registrieren ihre Berechtigungen im ServiceProvider:
 *
 *   CisPermissionManager::register(
 *       slug:        'project.export',
 *       label:       'Projekt exportieren',
 *       module:      'ExportModule',
 *       description: 'Erlaubt den Export von Projekten als PDF/Word'
 *   );
 *
 * Prüfung in Controllern / Views:
 *   auth()->user()->hasPermission('project.export', $project->cis_row_id)
 *
 * Im Blade-Template:
 *   @can('project.export', $project)   ← via Gate-Integration
 *   @endcan
 */
class CisPermissionManager
{
    /** In-memory Registry (vor DB-Persist) */
    protected static array $registered = [];

    /** Prefix → Gruppenbezeichnung, z.B. 'group' → 'Gruppen' */
    protected static array $prefixLabels = [];

    // ────────────────────────────────────────────────────────────────────────
    // Registrierung
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Registriert ein Präfix mit einer lesbaren Gruppenbezeichnung.
     * Wird im ServiceProvider des jeweiligen Moduls aufgerufen.
     *
     *   CisPermissionManager::registerGroup('project', 'Projekte');
     */
    public static function registerGroup(string $prefix, string $label): void
    {
        self::$prefixLabels[$prefix] = $label;
    }

    public static function getPrefixLabel(string $prefix): string
    {
        return self::$prefixLabels[$prefix] ?? ucfirst($prefix);
    }

    /**
     * Registriert eine Berechtigung.
     * Wird bei jedem Boot aufgerufen – schreibt in DB wenn noch nicht vorhanden.
     */
    public static function register(
        string  $slug,
        string  $label,
        ?string $module      = null,
        ?string $description = null
    ): void {
        self::$registered[$slug] = compact('slug', 'label', 'module', 'description');
    }

    /**
     * Persistiert alle registrierten Berechtigungen in die DB.
     * Wird vom ServiceProvider nach dem Boot aller Module aufgerufen.
     */
    public static function syncToDatabase(): void
    {
        foreach (self::$registered as $data) {
            Permission::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'label'       => $data['label'],
                    'module'      => $data['module'],
                    'description' => $data['description'],
                ]
            );
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Abfragen
    // ────────────────────────────────────────────────────────────────────────

    /** Alle bekannten Berechtigungen (aus Registry + DB) */
    public static function all(): Collection
    {
        return Permission::orderBy('module')->orderBy('slug')->get();
    }

    /** Alle Berechtigungen eines Moduls */
    public static function forModule(string $module): Collection
    {
        return Permission::where('module', $module)->orderBy('slug')->get();
    }

    /**
     * Alle Berechtigungen gruppiert nach Präfix-Label.
     * 'group.create' landet unter dem Label von 'group' (z.B. 'Gruppen').
     * Sortierung: alphabetisch nach Label, innerhalb nach Slug.
     */
    public static function grouped(): Collection
    {
        return Permission::orderBy('slug')->get()
            ->groupBy(function ($p) {
                $prefix = explode('.', $p->slug)[0];
                return self::getPrefixLabel($prefix);
            })
            ->sortKeys();
    }

    public static function has(string $slug): bool
    {
        return isset(self::$registered[$slug]) || Permission::where('slug', $slug)->exists();
    }
}
