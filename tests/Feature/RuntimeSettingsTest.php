<?php

namespace Tests\Feature;

use App\Http\Livewire\Setting\GeneralSettings;
use App\Http\Livewire\Setting\MailSettings;
use App\Models\Setting;
use App\Services\RuntimeSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class RuntimeSettingsTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    protected function tearDown(): void
    {
        config(['app.display_timezone' => 'Europe/Berlin']);

        parent::tearDown();
    }

    public function test_display_macro_converts_to_the_configured_timezone_without_touching_the_original(): void
    {
        $utc = Carbon::parse('2026-07-01 10:00', 'UTC');

        config(['app.display_timezone' => 'Europe/Berlin']);
        $this->assertSame('12:00', $utc->display()->format('H:i'));

        config(['app.display_timezone' => 'America/New_York']);
        $this->assertSame('06:00', $utc->display()->format('H:i'));
        $this->assertSame('10:00', $utc->format('H:i'), 'das Original bleibt in UTC');
        $this->assertSame('06:00', now()->setTimezone('UTC')->setTime(10, 0)->display()->format('H:i'), 'auch für now()/Model-Zeitstempel');
    }

    public function test_saved_timezone_is_applied_at_startup_and_changes_input_and_display_of_tender_dates(): void
    {
        Setting::set('display_timezone', 'America/New_York');
        RuntimeSettings::apply();
        $this->assertSame('America/New_York', config('app.display_timezone'));

        $project = $this->makeProject('draft', 'product', ['tender_starts_at' => '2026-10-18 10:00']);
        $this->assertSame('2026-10-18 14:00:00', \DB::table('projects')->where('cis_row_id', (string) $project->cis_row_id)->value('tender_starts_at'), 'intern UTC: 10:00 New York = 14:00 UTC');
        $this->assertSame('10:00', $project->fresh()->tender_starts_at->format('H:i'));

        config(['app.display_timezone' => 'Europe/Berlin']);
        $this->assertSame('16:00', $project->fresh()->tender_starts_at->format('H:i'), 'derselbe Zeitpunkt, anders dargestellt');
    }

    public function test_invalid_timezones_are_ignored_at_startup(): void
    {
        Setting::set('display_timezone', 'Mars/Olympus');
        RuntimeSettings::apply();

        $this->assertSame('Europe/Berlin', config('app.display_timezone'));
    }

    public function test_general_settings_validate_and_persist_the_timezone(): void
    {
        $this->actingAs($this->makeAdmin());

        Livewire::test(GeneralSettings::class)->set('displayTimezone', 'Nirgendwo/Land')->call('save')->assertHasErrors('displayTimezone');
        Livewire::test(GeneralSettings::class)->set('displayTimezone', 'Asia/Tokyo')->call('save')->assertHasNoErrors();

        $this->assertSame('Asia/Tokyo', Setting::get('display_timezone'));
        $this->assertSame('Asia/Tokyo', config('app.display_timezone'));
    }

    public function test_mail_settings_require_the_mail_permission(): void
    {
        $this->actingAs($this->makeUser());

        Livewire::test(MailSettings::class)->assertForbidden();
    }

    public function test_mail_settings_are_stored_with_an_encrypted_password_and_override_the_configuration(): void
    {
        $this->actingAs($this->makeAdmin());

        Livewire::test(MailSettings::class)
            ->set('host', 'smtp.example.org')->set('port', '465')->set('encryption', 'ssl')
            ->set('username', 'mailer')->set('password', 'Geheim#1')
            ->set('fromAddress', 'cis@example.org')->set('fromName', 'CIS')
            ->call('save')->assertHasNoErrors()->assertSet('password', '')->assertSet('hasPassword', true);

        $stored = Setting::get('mail_password');
        $this->assertNotSame('Geheim#1', $stored, 'das Passwort liegt nicht im Klartext in der Datenbank');
        $this->assertSame('Geheim#1', Crypt::decryptString($stored));

        $this->assertSame('smtp.example.org', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('Geheim#1', config('mail.mailers.smtp.password'));
        $this->assertSame('cis@example.org', config('mail.from.address'));

        $html = Livewire::test(MailSettings::class)->html();
        $this->assertStringNotContainsString('Geheim#1', $html, 'das Passwort wird nie wieder angezeigt');
        $this->assertStringContainsString('unverändert', $html);
    }

    public function test_leaving_the_password_empty_keeps_the_stored_one_and_validation_rejects_bad_values(): void
    {
        $this->actingAs($this->makeAdmin());
        RuntimeSettings::storeMail('smtp.a.test', 587, 'starttls', 'u', 'AltesPasswort', 'a@a.test', 'A');

        Livewire::test(MailSettings::class)->set('host', 'smtp.b.test')->set('password', '')->call('save')->assertHasNoErrors();
        $this->assertSame('AltesPasswort', RuntimeSettings::decryptedPassword());

        Livewire::test(MailSettings::class)->set('host', '')->set('port', 'abc')->set('fromAddress', 'keine-mail')->call('save')
            ->assertHasErrors(['host', 'port', 'fromAddress']);
    }

    public function test_encryption_modes_map_to_the_matching_smtp_options(): void
    {
        RuntimeSettings::storeMail('h.test', 587, 'starttls', '', null, 'a@a.test', '');
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertTrue(config('mail.mailers.smtp.require_tls'));

        RuntimeSettings::storeMail('h.test', 25, 'none', '', null, 'a@a.test', '');
        $this->assertFalse(config('mail.mailers.smtp.auto_tls'));
        $this->assertFalse(config('mail.mailers.smtp.require_tls'));
    }

    public function test_test_mail_is_sent_with_the_form_values_and_the_stored_configuration_is_restored(): void
    {
        Mail::fake();
        $this->actingAs($this->makeAdmin());
        RuntimeSettings::storeMail('gespeichert.test', 587, 'starttls', '', null, 'cis@example.org', 'CIS');

        Livewire::test(MailSettings::class)
            ->set('host', 'formular.test')->set('testRecipient', 'ich@example.test')
            ->call('sendTest')->assertSet('testOk', true);

        $this->assertSame('gespeichert.test', config('mail.mailers.smtp.host'), 'nach dem Test gilt wieder die gespeicherte Konfiguration');
    }

    public function test_the_debug_login_shortcut_exists_only_in_local_and_test_environments(): void
    {
        $php = PHP_BINARY;
        $uris = fn (string $env) => array_column(json_decode(Process::path(base_path())->run([$php, 'artisan', 'route:list', "--env={$env}", '--path=Debug', '--json'])->output(), true) ?? [], 'uri');

        $this->assertSame(['Debug/Login'], $uris('local'));
        $this->assertSame([], $uris('production'));
    }
}
