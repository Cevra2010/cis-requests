<?php

namespace App\Providers;

use CisFoundation\CisMenuManager\MenuManager;
use CisFoundation\CisPermissionManager\CisPermissionManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerMainMenu();
        $this->registerPermissions();
    }

    protected function registerPermissions(): void
    {
        CisPermissionManager::register(
            'project.tender.override_lock',
            'Fixierte Ausschreibung bearbeiten',
            description: 'Erlaubt das Bearbeiten von Produkten und Ausschreibungstext, nachdem ein Projekt fixiert wurde.'
        );
    }

    protected function registerMainMenu(): void
    {
        $menu = MenuManager::registerMenu('main');

        $menu->registerEntry('home')
            ->setText('Dashboard')
            ->setRoute('dashboard')
            ->setIcon('house')
            ->setPriority(10);

        $menu->registerEntry('projects')
            ->setText('Projekte')
            ->setRoute('project')
            ->setIcon('folder-open')
            ->setPriority(20);

        $menu->registerEntry('product')
            ->setText('Produktdatensätze')
            ->setRoute('product')
            ->setIcon('box-archive')
            ->setPriority(30);

        $menu->registerEntry('source')
            ->setText('Produktquellen')
            ->setRoute('source')
            ->setIcon('truck')
            ->setPriority(40);

        $menu->registerEntry('price')
            ->setText('Preispflege')
            ->setRoute('price')
            ->setIcon('tag')
            ->setPriority(50);

        $menu->registerEntry('user')
            ->setText('Benutzer')
            ->setRoute('user')
            ->setIcon('users')
            ->setPriority(80);

        $menu->registerEntry('group')
            ->setText('Gruppen')
            ->setRoute('group.index')
            ->setIcon('people-group')
            ->setPriority(85);

        $menu->registerEntry('role')
            ->setText('Rollen')
            ->setRoute('role.index')
            ->setIcon('id-badge')
            ->setPriority(87);

        $menu->registerEntry('category')
            ->setText('Kategorien')
            ->setRoute('category.index')
            ->setIcon('tag')
            ->setPriority(90);

        $menu->registerEntry('modules')
            ->setText('Module')
            ->setIcon('puzzle-piece')
            ->setPriority(95);

        $menu->registerEntry('modules.manager')
            ->setText('Module verwalten')
            ->setRoute('modules')
            ->setParent('modules')
            ->setPriority(1);

        $menu->registerEntry('setting')
            ->setText('Einstellungen')
            ->setRoute('setting')
            ->setIcon('gear')
            ->setPriority(100);
    }
}
