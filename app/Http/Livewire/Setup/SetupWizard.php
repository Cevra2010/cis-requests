<?php

namespace App\Http\Livewire\Setup;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\DemoDataService;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class SetupWizard extends Component
{
    /** 1 = Firmenlizenz, 2 = Administrator-Konto, 3 = Grundeinstellungen & Abschluss */
    public int $step = 1;

    public bool $completed = false;

    // ── Schritt 1: Firmenlizenz ─────────────────────────────────────────────
    public string $licenseKey = '';
    public ?string $licenseeName = null;

    // ── Schritt 2: Administrator-Konto ───────────────────────────────────────
    public string $firstname = '';
    public string $lastname = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // ── Schritt 3: Grundeinstellungen ────────────────────────────────────────
    public string $defaultMinOrderValue = '0';
    public bool $wantsDemoData = false;

    /** @var array<string, int> */
    public array $demoSummary = [];

    public function mount(): void
    {
        if (User::query()->exists()) {
            $this->redirect(route('auth.login'), navigate: false);
            return;
        }

        $licenseService = app(LicenseService::class);
        if ($licenseService->hasMasterLicense()) {
            $this->licenseeName = $licenseService->installationLicensee();
            $this->step = 2;
        }
    }

    public function activateLicense(): void
    {
        $this->validate(['licenseKey' => 'required|string']);

        $result = app(LicenseService::class)->activateMaster(trim($this->licenseKey));

        if (! $result['valid']) {
            $this->addError('licenseKey', $result['error']);
            return;
        }

        $this->licenseeName = $result['licensee'];
        $this->step = 2;
    }

    public function nextFromAccount(): void
    {
        $this->validate([
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        $this->step = 3;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function finish(): void
    {
        $this->validate([
            'defaultMinOrderValue' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () {
            $role = Role::firstOrCreate(
                ['name' => 'Administrator'],
                ['description' => 'Vollzugriff auf alle Funktionen.', 'color' => '#4f46e5']
            );

            foreach (Permission::pluck('slug') as $slug) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $role->cis_row_id, 'permission_slug' => $slug, 'project_id' => null],
                    ['granted' => true]
                );
            }

            $user = User::create([
                'firstname' => $this->firstname,
                'lastname'  => $this->lastname,
                'email'     => $this->email,
                'password'  => Hash::make($this->password),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $role->users()->attach((string) $user->cis_row_id);

            if (trim($this->defaultMinOrderValue) !== '') {
                Setting::set('default_min_order_value', (float) str_replace(',', '.', $this->defaultMinOrderValue));
            }

            if ($this->wantsDemoData) {
                $this->demoSummary = app(DemoDataService::class)->run();
            }

            Auth::login($user);
        });

        request()->session()->regenerate();
        $this->completed = true;
    }

    public function render()
    {
        return view('livewire.setup.setup-wizard');
    }
}
