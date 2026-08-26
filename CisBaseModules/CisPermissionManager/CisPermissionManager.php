<?php

namespace CisFoundation\CisPermissionManager;

use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        self::grantUngrantedToAdministrator();
    }

    /**
     * Sicherheitsnetz gegen ein wiederkehrendes Problem: Eine neu im Code
     * registrierte Berechtigung ist zunächst niemandem zugewiesen – ohne
     * diesen Ausgleich würde jede neue Berechtigung (z.B. für einen neuen
     * Statusübergang) die zugehörige Funktion für ALLE Benutzer inkl. der
     * Administrator-Rolle sperren, bis sie jemand manuell zuweist.
     *
     * Läuft bei jedem Boot: die Administrator-Rolle bekommt automatisch jede
     * registrierte Berechtigung, für die sie noch KEINEN Eintrag hat (weder
     * gewährt noch explizit entzogen) – ein bestehender, bewusst gesetzter
     * Eintrag (auch ein Entzug) bleibt unangetastet.
     */
    private static function grantUngrantedToAdministrator(): void
    {
        $slugs = array_keys(self::$registered);
        if (empty($slugs)) {
            return;
        }

        $adminRoleId = DB::table('roles')
            ->where('name', 'Administrator')
            ->whereNull('deleted_at')
            ->value('cis_row_id');

        if (! $adminRoleId) {
            return;
        }

        $existingSlugs = DB::table('role_permissions')
            ->where('role_id', $adminRoleId)
            ->whereNull('project_id')
            ->whereIn('permission_slug', $slugs)
            ->pluck('permission_slug')
            ->all();

        $missing = array_diff($slugs, $existingSlugs);
        if (empty($missing)) {
            return;
        }

        DB::table('role_permissions')->insert(array_map(fn (string $slug) => [
            'role_id'         => $adminRoleId,
            'permission_slug' => $slug,
            'project_id'      => null,
            'granted'         => true,
        ], $missing));
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
