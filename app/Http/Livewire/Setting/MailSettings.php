<?php

namespace App\Http\Livewire\Setting;

use App\Services\RuntimeSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Einstellungen → E-Mail: SMTP-Server, Zugangsdaten, Absender und Test-Versand. Gespeichert in den Einstellungen, Passwort verschlüsselt. */
class MailSettings extends Component
{
    public string $host = '';
    public string $port = '587';
    public string $encryption = 'starttls';
    public string $username = '';
    public string $password = '';
    public string $fromAddress = '';
    public string $fromName = '';
    public bool $hasPassword = false;

    public string $testRecipient = '';
    public ?string $testResult = null;
    public bool $testOk = false;
    public bool $saved = false;

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasPermission('system.mail'), 403);
    }

    public function mount(): void
    {
        $this->authorizeManage();
        $form = RuntimeSettings::mailForForm();

        // Noch nichts gespeichert: die aktuell wirksamen .env-Werte als Vorschlag zeigen (ohne Passwort).
        if ($form['host'] === '') {
            $form['host']       = (string) config('mail.mailers.smtp.host');
            $form['port']       = (string) config('mail.mailers.smtp.port');
            $form['username']   = (string) config('mail.mailers.smtp.username');
            $form['fromAddress'] = (string) config('mail.from.address');
            $form['fromName']   = (string) config('mail.from.name');
        }

        $this->host        = $form['host'];
        $this->port        = $form['port'];
        $this->encryption  = $form['encryption'];
        $this->username    = $form['username'];
        $this->fromAddress = $form['fromAddress'];
        $this->fromName    = $form['fromName'];
        $this->hasPassword = $form['hasPassword'];
        $this->testRecipient = (string) auth()->user()?->email;
    }

    private function rules_(): array
    {
        return [
            'host'        => 'required|string|max:255',
            'port'        => 'required|integer|between:1,65535',
            'encryption'  => ['required', Rule::in(array_keys(RuntimeSettings::MAIL_ENCRYPTIONS))],
            'username'    => 'nullable|string|max:255',
            'password'    => 'nullable|string|max:255',
            'fromAddress' => 'required|email|max:255',
            'fromName'    => 'nullable|string|max:255',
        ];
    }

    private function messages_(): array
    {
        return [
            'host.required'        => 'Bitte den SMTP-Server angeben.',
            'port.integer'         => 'Der Port muss eine Zahl sein.',
            'port.between'         => 'Der Port muss zwischen 1 und 65535 liegen.',
            'fromAddress.required' => 'Bitte eine Absenderadresse angeben.',
            'fromAddress.email'    => 'Die Absenderadresse ist keine gültige E-Mail-Adresse.',
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();
        $this->validate($this->rules_(), $this->messages_());

        RuntimeSettings::storeMail(trim($this->host), (int) $this->port, $this->encryption, trim($this->username), $this->password, trim($this->fromAddress), trim($this->fromName));

        $this->hasPassword = RuntimeSettings::decryptedPassword() !== null || $this->hasPassword || $this->password !== '';
        if (trim($this->username) === '') {
            $this->hasPassword = false;
        }
        $this->password = '';
        $this->saved    = true;
    }

    public function resetToDefaults(): void
    {
        $this->authorizeManage();
        RuntimeSettings::resetMail();
        $this->redirect(request()->header('Referer') ?: route('setting'), navigate: false);
    }

    /** Sendet eine Test-E-Mail mit den Werten aus dem Formular (ungespeichert), danach gilt wieder die gespeicherte Konfiguration. */
    public function sendTest(): void
    {
        $this->authorizeManage();
        $this->validate($this->rules_() + ['testRecipient' => 'required|email'], $this->messages_() + ['testRecipient.required' => 'Bitte einen Empfänger angeben.', 'testRecipient.email' => 'Der Empfänger ist keine gültige E-Mail-Adresse.']);

        $password = $this->password !== '' ? $this->password : RuntimeSettings::decryptedPassword();
        $stored   = \App\Models\Setting::whereIn('key', ['mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name'])->pluck('value', 'key')->all();

        RuntimeSettings::applyMail([
            'mail_host'         => trim($this->host),
            'mail_port'         => $this->port,
            'mail_encryption'   => $this->encryption,
            'mail_username'     => trim($this->username),
            'mail_password'     => $password !== null && $password !== '' ? \Illuminate\Support\Facades\Crypt::encryptString($password) : null,
            'mail_from_address' => trim($this->fromAddress),
            'mail_from_name'    => trim($this->fromName),
        ]);

        try {
            Mail::raw(
                "Dies ist eine Test-E-Mail von CIS Requests.\n\nWenn du sie liest, funktioniert der E-Mail-Versand mit diesen Einstellungen.",
                fn ($message) => $message->to($this->testRecipient)->subject('CIS Requests: Test-E-Mail')
            );
            $this->testOk     = true;
            $this->testResult = 'Test-E-Mail an ' . $this->testRecipient . ' wurde versendet.';
        } catch (\Throwable $e) {
            $this->testOk     = false;
            $this->testResult = 'Versand fehlgeschlagen: ' . \Illuminate\Support\Str::limit($e->getMessage(), 300);
        } finally {
            // Gespeicherte Konfiguration (oder .env, falls nichts gespeichert) wieder herstellen.
            RuntimeSettings::applyMail($stored);
        }
    }

    public function render()
    {
        $this->authorizeManage();

        return view('livewire.setting.mail-settings', [
            'encryptions'   => RuntimeSettings::MAIL_ENCRYPTIONS,
            'envHost'       => config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'),
            'usingSettings' => RuntimeSettings::mailForForm()['host'] !== '',
        ]);
    }
}
