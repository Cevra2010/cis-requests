<?php

namespace App\Providers;

use App\Events\WorkflowEventOccurred;
use App\Listeners\RunWorkflowTriggers;
use App\Services\Workflow\Actions\EmailAction;
use App\Services\Workflow\Actions\FixationAction;
use App\Services\Workflow\Actions\GotoStepAction;
use App\Services\Workflow\Events\TenderEndedEvent;
use App\Services\Workflow\Events\TenderStartedEvent;
use App\Services\Workflow\Events\WorkflowEventTypes;
use App\Services\Workflow\Actions\WorkflowActionTypes;
use CisFoundation\CisMenuManager\MenuManager;
use CisFoundation\CisPermissionManager\CisPermissionManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerMainMenu();
        $this->registerPermissions();

        // Core-Maßnahmenart "E-Mail senden". Künftige Modul-Maßnahmen registrieren sich
        // analog im jeweiligen Modul-ServiceProvider (läuft dann nur bei aktivem Modul).
        WorkflowActionTypes::register(new EmailAction());
        WorkflowActionTypes::register(new GotoStepAction());
        foreach (array_keys(FixationAction::SCOPES) as $scope) {
            WorkflowActionTypes::register(new FixationAction($scope));
        }

        // Workflow-Ereignisse (Trigger): Core-Arten hier, weitere in den Modul-ServiceProvidern.
        WorkflowEventTypes::register(new TenderStartedEvent());
        WorkflowEventTypes::register(new TenderEndedEvent());
        Event::listen(WorkflowEventOccurred::class, RunWorkflowTriggers::class);

        // Cron-Statusanzeige: jede vom Scheduler ausgeführte Aufgabe protokollieren.
        Event::listen(\Illuminate\Console\Events\ScheduledTaskFinished::class, \App\Listeners\RecordCronRun::class);
        Event::listen(\Illuminate\Console\Events\ScheduledTaskFailed::class, \App\Listeners\RecordCronRun::class);
    }

    protected function registerPermissions(): void
    {
        // Wer Projekt-Workflow-Schritte freigeben darf, wird seit v0.9.0.0 nicht mehr über
        // Permission-Slugs geregelt, sondern direkt im Workflow-Bildschirm konfiguriert
        // (siehe App\Services\Workflow\WorkflowApprover). Nur die Konfiguration DIESES
        // Bildschirms selbst bleibt eine normale Permission – sicherheitsrelevant, weil sich
        // ein Editor sonst selbst zum Freigeber machen könnte.
        CisPermissionManager::registerGroup('workflow', 'Workflow');
        CisPermissionManager::register(
            'workflow.manage',
            'Workflow verwalten',
            description: 'Erlaubt das Bearbeiten der Projekt-Workflows (Schritte, Freigeber, Maßnahmen).'
        );

        CisPermissionManager::registerGroup('system', 'System');
        CisPermissionManager::register(
            'system.scheduler',
            'Planer (Cron) einsehen',
            description: 'Erlaubt die Statusanzeige des Cron-Planers, die Cron-Trigger-URL und das manuelle Ausführen des Schedulers in den Einstellungen.'
        );
        CisPermissionManager::register(
            'system.reset',
            'System zurücksetzen',
            description: 'Erlaubt destruktive Reset-Vorgänge (Preise, Produktdaten, Systemdaten, Werkseinstellung) sowie das Einspielen von Demo-Daten.'
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

        $menu->registerEntry('workflow')
            ->setText('Workflow')
            ->setRoute('workflow')
            ->setIcon('diagram-project')
            ->setPriority(15)
            ->setVisible(fn () => auth()->user()?->hasPermission('workflow.manage') ?? false);

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
            ->setText('Ordnung')
            ->setRoute('category.index')
            ->setIcon('tag')
            ->setPriority(90);

        $menu->registerEntry('parameter')
            ->setText('Fahrzeugparameter')
            ->setRoute('parameter.index')
            ->setIcon('list-tree')
            ->setPriority(91);

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
