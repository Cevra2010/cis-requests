<?php

namespace CisFoundation\CisMenuManager;

use Illuminate\Support\Facades\Route;

/**
 * MenuEntry – ein einzelner Navigationseintrag.
 *
 * Module können Einträge mit Priority positionieren:
 *   $menu->registerEntry('min-quality')
 *       ->setText('Mindestqualität')
 *       ->setRoute('min-quality.index')
 *       ->setIcon('shield-check')
 *       ->setPriority(25)          // zwischen priority 20 (Produkte) und 30 (Einstellungen)
 *       ->setVisible(fn() => auth()->user()->can('min-quality.view'));
 */
class MenuEntry
{
    public string  $slug;
    public ?string $text           = null;
    public ?string $route          = null;
    public array   $routeParameters = [];
    public ?string $url            = null;
    public ?string $icon           = null;
    public ?string $parent_slug    = null;
    public bool    $openInNewWindow = false;
    public int     $priority       = 10;
    public string  $badge          = '';

    /** Referenz auf das übergeordnete Menu-Objekt */
    protected Menu $menu;

    /** Sichtbarkeits-Closure: fn() => bool */
    protected ?\Closure $visibleWhen = null;

    public function __construct(string $slug, Menu $menu)
    {
        $this->slug = $slug;
        $this->menu = $menu;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Fluent API
    // ────────────────────────────────────────────────────────────────────────

    public function setText(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function setRoute(string $route, array $parameters = []): static
    {
        $this->route           = $route;
        $this->routeParameters = $parameters;
        return $this;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function setParent(string $parentSlug): static
    {
        $this->parent_slug = $parentSlug;
        return $this;
    }

    public function setNewWindow(bool $value = true): static
    {
        $this->openInNewWindow = $value;
        return $this;
    }

    /**
     * Setzt die Reihenfolge des Eintrags (kleinere Zahl = weiter oben).
     * Standard ist 10; Abstände von 10 ermöglichen einfaches Einfügen durch Module.
     */
    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Optionaler Badge-Text (z.B. "NEU", Anzahl offener Aufgaben).
     */
    public function setBadge(string $badge): static
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * Closure die bestimmt ob der Eintrag sichtbar ist.
     * fn() => bool
     *
     * Beispiel:
     *   ->setVisible(fn() => auth()->user()->can('admin'))
     */
    public function setVisible(\Closure $condition): static
    {
        $this->visibleWhen = $condition;
        return $this;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Getter / Zustand
    // ────────────────────────────────────────────────────────────────────────

    public function isVisible(): bool
    {
        if ($this->visibleWhen === null) {
            return true;
        }
        return (bool) ($this->visibleWhen)();
    }

    public function isCurrent(): bool
    {
        if (!$this->route || !Route::current()) {
            return false;
        }
        $currentName = Route::current()->getName() ?? '';
        return str_starts_with($currentName, $this->route);
    }

    public function getUrl(): string
    {
        if ($this->url !== null) {
            return $this->url;
        }

        if ($this->route !== null) {
            if (!Route::has($this->route)) {
                return '#';
            }
            return count($this->routeParameters)
                ? route($this->route, $this->routeParameters)
                : route($this->route);
        }

        return '#';
    }
}
