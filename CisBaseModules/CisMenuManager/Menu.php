<?php

namespace CisFoundation\CisMenuManager;

use CisFoundation\CisHookManager\CisHooks;
use CisFoundation\CisMenuManager\Exception\MenuViewNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

/**
 * Menu – eine benannte Navigation.
 *
 * Module erweitern das Menü über den Hook-Bus:
 *
 *   CisHooks::addFilter('menu.main.entries', function(Collection $entries) {
 *       $entry = new MenuEntry('min-quality', $menu);   // Achtung: menu-Referenz benötigt
 *       ...
 *       return $entries;
 *   }, module: 'MinQualityModule');
 *
 * Oder direkter über MenuManager::extend() (empfohlen):
 *
 *   Menu::extend('main', function(Menu $menu) {
 *       $menu->registerEntry('min-quality')
 *           ->setText('Mindestqualität')
 *           ->setRoute('min-quality.index')
 *           ->setPriority(25);
 *   }, module: 'MinQualityModule');
 */
class Menu
{
    public string $slug;
    public string $template = 'cis-menu::menu';

    protected Collection $entryCollection;

    /** Static Registry aller Menus – wird von MenuManager verwaltet */
    public static ?Collection $menuCollection = null;

    public function __construct(string $slug)
    {
        $this->slug            = $slug;
        $this->entryCollection = collect();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Einträge registrieren / entfernen
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Registriert einen neuen Menüeintrag und gibt ihn zurück (Fluent-Fortsetzung).
     */
    public function registerEntry(string $slug): MenuEntry
    {
        $entry = new MenuEntry($slug, $this);
        $this->entryCollection->push($entry);
        return $entry;
    }

    /**
     * Entfernt einen Menüeintrag anhand seines Slugs.
     * Module rufen dies beim Deinstallieren auf.
     */
    public function removeEntry(string $slug): static
    {
        $this->entryCollection = $this->entryCollection
            ->filter(fn(MenuEntry $e) => $e->slug !== $slug)
            ->values();
        return $this;
    }

    /**
     * Entfernt alle Einträge die ein Modul registriert hat.
     * (Module müssen ihre Slugs mit einem Präfix kennzeichnen oder einzeln entfernen.)
     */
    public function removeEntriesByModule(string $module): static
    {
        // Einfachste Implementierung: Hooks aufräumen
        CisHooks::removeModuleHooks($module);
        return $this;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Template
    // ────────────────────────────────────────────────────────────────────────

    public function setTemplatePath(string $templatePath): static
    {
        if (!View::exists($templatePath)) {
            throw new MenuViewNotFoundException("Menu-View \"{$templatePath}\" nicht gefunden.");
        }
        $this->template = $templatePath;
        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Einträge abrufen (mit Hook-Integration)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Führt finale Schritte aus bevor das Menu gerendert wird:
     * 1. Module-Hooks anwenden (neue Einträge hinzufügen / entfernen)
     * 2. Unsichtbare Einträge filtern
     * 3. Nach Priority sortieren
     */
    public function finalize(): static
    {
        // Module können Einträge hinzufügen oder entfernen
        $this->entryCollection = CisHooks::applyFilter(
            'menu.' . $this->slug . '.entries',
            $this->entryCollection
        );

        // Unsichtbare Einträge entfernen
        $this->entryCollection = $this->entryCollection
            ->filter(fn(MenuEntry $e) => $e->isVisible())
            ->values();

        // Nach Priority sortieren
        $this->entryCollection = $this->entryCollection
            ->sortBy(fn(MenuEntry $e) => $e->priority)
            ->values();

        return $this;
    }

    public function getParentEntries(): Collection
    {
        return $this->entryCollection->where('parent_slug', null)->values();
    }

    public function getChildEntries(string $parentSlug): Collection
    {
        return $this->entryCollection->where('parent_slug', $parentSlug)->values();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Statische Hilfsmethode (Kurzform für MenuManager::get())
    // ────────────────────────────────────────────────────────────────────────

    public static function get(string $slug): static
    {
        return MenuManager::get($slug);
    }

    /**
     * Modul-sicherer Erweiterungs-Einstiegspunkt.
     * Im ServiceProvider des Moduls:
     *
     *   Menu::extend('main', function(Menu $menu) {
     *       $menu->registerEntry('my-feature')
     *           ->setText('Mein Feature')
     *           ->setRoute('my-feature.index')
     *           ->setPriority(25);
     *   }, module: 'MyModule');
     */
    public static function extend(string $slug, callable $callback, ?string $module = null): void
    {
        CisHooks::addFilter(
            'menu.' . $slug . '.entries',
            function (Collection $entries) use ($slug, $callback): Collection {
                // Temporäres Menu-Objekt für die Erweiterung
                $tempMenu = new static($slug);
                $callback($tempMenu);
                // Neue Einträge anhängen
                $tempMenu->entryCollection->each(fn($e) => $entries->push($e));
                return $entries;
            },
            priority: 10,
            module:   $module
        );
    }
}
