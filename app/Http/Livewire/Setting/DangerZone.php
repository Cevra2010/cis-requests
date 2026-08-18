<?php

namespace App\Http\Livewire\Setting;

use App\Services\DemoDataService;
use App\Services\SystemResetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DangerZone extends Component
{
    public const ACTIONS = [
        'prices' => [
            'label'       => 'Preise zurücksetzen',
            'description' => 'Löscht alle erfassten Preise. Produkte, Quellen und Projekte bleiben unverändert.',
            'phrase'      => 'PREISE ZURÜCKSETZEN',
        ],
        'products' => [
            'label'       => 'Produktdaten zurücksetzen',
            'description' => 'Löscht den gesamten Produktkatalog (Produkte, Preise, Beschreibungen, Parameter). Produktquellen und Projekte bleiben erhalten.',
            'phrase'      => 'PRODUKTE ZURÜCKSETZEN',
        ],
        'system' => [
            'label'       => 'System zurücksetzen',
            'description' => 'Löscht alle Projekte, Produkte, Quellen, Kategorien, Angebote und Gruppen. Benutzerkonten, Rollen und deren Berechtigungen bleiben erhalten.',
            'phrase'      => 'SYSTEM ZURÜCKSETZEN',
        ],
        'factory' => [
            'label'       => 'Werkseinstellungen',
            'description' => 'Löscht wirklich alle Daten inklusive Benutzerkonten und Rollen – der Zustand direkt nach einer Neuinstallation. Du wirst danach abgemeldet und der Einrichtungs-Assistent startet.',
            'phrase'      => 'WERKSEINSTELLUNGEN',
        ],
    ];

    public ?string $activeAction = null;

    public string $confirmText = '';

    public ?string $lastMessage = null;

    public ?array $lastSummary = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasPermission('system.reset'), 403);
    }

    public function open(string $action): void
    {
        if (! isset(self::ACTIONS[$action])) {
            return;
        }

        $this->activeAction = $action;
        $this->confirmText  = '';
        $this->resetErrorBag('confirmText');
    }

    public function cancel(): void
    {
        $this->activeAction = null;
        $this->confirmText  = '';
    }

    public function confirmReset(): void
    {
        abort_unless(Auth::user()?->hasPermission('system.reset'), 403);

        $action = $this->activeAction;
        if (! $action || ! isset(self::ACTIONS[$action])) {
            return;
        }

        $expected = self::ACTIONS[$action]['phrase'];
        if (mb_strtoupper(trim($this->confirmText)) !== $expected) {
            $this->addError('confirmText', 'Bitte gib exakt „' . $expected . '" ein, um zu bestätigen.');
            return;
        }

        $service = app(SystemResetService::class);

        $summary = match ($action) {
            'prices'   => $service->resetPrices(),
            'products' => $service->resetProductData(),
            'system'   => $service->resetSystem(),
            'factory'  => $service->factoryReset(),
        };

        $this->activeAction = null;
        $this->confirmText  = '';

        if ($action === 'factory') {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            $this->redirect(route('home'), navigate: false);
            return;
        }

        $this->lastMessage = self::ACTIONS[$action]['label'] . ' wurde ausgeführt.';
        $this->lastSummary = $summary;
    }

    public function runDemoData(): void
    {
        abort_unless(Auth::user()?->hasPermission('system.reset'), 403);

        $this->lastMessage = 'Demo-Daten wurden eingespielt.';
        $this->lastSummary = app(DemoDataService::class)->run();
    }

    public function render()
    {
        return view('livewire.setting.danger-zone', ['actions' => self::ACTIONS]);
    }
}
