<?php

namespace CisFoundation\CisPermissionManager;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CisPermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('cis.permissions', fn() => new CisPermissionManager());
    }

    public function boot(): void
    {
        // Core-Berechtigungen registrieren
        $this->registerCorePermissions();

        // Laravel Gate-Integration: @can('project.export', $project)
        Gate::before(function ($user, $ability, $arguments) {
            $projectId = isset($arguments[0]) ? $arguments[0]?->cis_row_id : null;
            if ($user->hasPermission($ability, $projectId)) {
                return true;
            }
            return null; // weiter zum nächsten Gate-Check
        });

        // Nach dem Booten aller ServiceProvider in DB synchronisieren
        $this->app->booted(function () {
            try {
                CisPermissionManager::syncToDatabase();
            } catch (\Throwable) {
                // DB noch nicht verfügbar (z.B. während migrate) – ignorieren
            }
        });
    }

    protected function registerCorePermissions(): void
    {
        // ── Gruppen-Labels (Präfix → lesbarer Name) ──────────────────────────
        CisPermissionManager::registerGroup('user',     'Benutzer');
        CisPermissionManager::registerGroup('group',    'Gruppen');
        CisPermissionManager::registerGroup('role',     'Rollen');
        CisPermissionManager::registerGroup('project',  'Projekte');
        CisPermissionManager::registerGroup('category', 'Kategorien');
        CisPermissionManager::registerGroup('product',  'Produkte');
        CisPermissionManager::registerGroup('source',   'Quellen');
        CisPermissionManager::registerGroup('settings', 'Einstellungen');

        // ── Benutzer ─────────────────────────────────────────────────────────
        CisPermissionManager::register('user.view',   'Benutzer anzeigen',   description: 'Benutzerliste einsehen');
        CisPermissionManager::register('user.create', 'Benutzer erstellen',  description: 'Neue Benutzer anlegen');
        CisPermissionManager::register('user.edit',   'Benutzer bearbeiten', description: 'Benutzerdaten ändern');
        CisPermissionManager::register('user.delete', 'Benutzer löschen',    description: 'Benutzer deaktivieren/löschen');

        // ── Gruppen ───────────────────────────────────────────────────────────
        CisPermissionManager::register('group.view',   'Gruppen anzeigen',   description: 'Gruppenliste einsehen');
        CisPermissionManager::register('group.create', 'Gruppen erstellen',  description: 'Neue Gruppen anlegen');
        CisPermissionManager::register('group.edit',   'Gruppen bearbeiten', description: 'Gruppendetails und Rechte ändern');
        CisPermissionManager::register('group.delete', 'Gruppen löschen',    description: 'Gruppen entfernen');

        // ── Rollen ────────────────────────────────────────────────────────────
        CisPermissionManager::register('role.view',   'Rollen anzeigen',   description: 'Rollenliste einsehen');
        CisPermissionManager::register('role.create', 'Rollen erstellen',  description: 'Neue Rollen anlegen');
        CisPermissionManager::register('role.edit',   'Rollen bearbeiten', description: 'Rollendetails und Rechte ändern');
        CisPermissionManager::register('role.delete', 'Rollen löschen',    description: 'Rollen entfernen');

        // ── Projekte ──────────────────────────────────────────────────────────
        CisPermissionManager::register('project.view',   'Projekte anzeigen',   description: 'Projektliste und Details einsehen');
        CisPermissionManager::register('project.create', 'Projekte erstellen',  description: 'Neue Projekte anlegen');
        CisPermissionManager::register('project.edit',   'Projekte bearbeiten', description: 'Projektdetails und Produkte bearbeiten');
        CisPermissionManager::register('project.delete', 'Projekte löschen',    description: 'Projekte archivieren/löschen');

        // ── Kategorien ────────────────────────────────────────────────────────
        CisPermissionManager::register('category.view',   'Kategorien anzeigen',   description: 'Kategorieliste einsehen');
        CisPermissionManager::register('category.create', 'Kategorien erstellen',  description: 'Neue Kategorien anlegen');
        CisPermissionManager::register('category.edit',   'Kategorien bearbeiten', description: 'Kategoriedetails ändern');
        CisPermissionManager::register('category.delete', 'Kategorien löschen',    description: 'Kategorien entfernen');

        // ── Produkte ──────────────────────────────────────────────────────────
        CisPermissionManager::register('product.view',   'Produkte anzeigen',   description: 'Produktdatensätze einsehen');
        CisPermissionManager::register('product.create', 'Produkte erstellen',  description: 'Neue Produktdatensätze anlegen');
        CisPermissionManager::register('product.edit',   'Produkte bearbeiten', description: 'Produktdaten ändern');
        CisPermissionManager::register('product.delete', 'Produkte löschen',    description: 'Produkte entfernen');

        // ── Quellen ───────────────────────────────────────────────────────────
        CisPermissionManager::register('source.view',   'Quellen anzeigen',   description: 'Produktquellen einsehen');
        CisPermissionManager::register('source.create', 'Quellen erstellen',  description: 'Neue Produktquellen anlegen');
        CisPermissionManager::register('source.edit',   'Quellen bearbeiten', description: 'Quelldaten ändern');
        CisPermissionManager::register('source.delete', 'Quellen löschen',    description: 'Quellen entfernen');

        // ── Einstellungen ─────────────────────────────────────────────────────
        CisPermissionManager::register('settings.view', 'Einstellungen anzeigen',   description: 'Systemeinstellungen einsehen');
        CisPermissionManager::register('settings.edit', 'Einstellungen bearbeiten', description: 'Systemeinstellungen ändern');
    }
}
