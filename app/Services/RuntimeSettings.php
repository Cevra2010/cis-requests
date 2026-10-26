<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

/**
 * In den Einstellungen gepflegte Werte, die beim Start der Anwendung die Konfiguration überschreiben:
 *  - Zeitzone für Eingabe und Anzeige von Zeitpunkten (intern wird weiterhin in UTC gespeichert),
 *  - SMTP-Versand (Host, Zugangsdaten, Absender). Ohne gespeicherte Werte gelten die .env-Einstellungen.
 */
class RuntimeSettings
{
    public const MAIL_ENCRYPTIONS = [
        'starttls' => 'STARTTLS (üblich: Port 587)',
        'ssl'      => 'SSL/TLS (üblich: Port 465)',
        'none'     => 'Keine Verschlüsselung (nur für Testsysteme)',
    ];

    private const MAIL_KEYS = ['mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name'];

    /** Wird vom AppServiceProvider beim Start aufgerufen; ohne Datenbank/Tabelle (Neuinstallation, Migration) bleibt es still wirkungslos. */
    public static function apply(): void
    {
        try {
            $values = Setting::whereIn('key', array_merge(['display_timezone'], self::MAIL_KEYS))->pluck('value', 'key')->all();
        } catch (\Throwable) {
            return;
        }

        self::applyTimezone($values['display_timezone'] ?? null);
        self::applyMail($values);
    }

    public static function applyTimezone(?string $timezone): void
    {
        if ($timezone && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            config(['app.display_timezone' => $timezone]);
        }
    }

    /** @param array<string, ?string> $values mail_*-Einstellungen (Passwort verschlüsselt, wie gespeichert) */
    public static function applyMail(array $values): void
    {
        $host = trim((string) ($values['mail_host'] ?? ''));
        if ($host === '') {
            return;
        }

        $encryption = $values['mail_encryption'] ?? 'starttls';
        $password   = null;
        if (! empty($values['mail_password'])) {
            try {
                $password = Crypt::decryptString($values['mail_password']);
            } catch (\Throwable) {
                $password = null; // z.B. nach einem Wechsel des APP_KEY: Passwort muss neu eingegeben werden
            }
        }

        config([
            'mail.default'                  => 'smtp',
            'mail.mailers.smtp.transport'   => 'smtp',
            'mail.mailers.smtp.host'        => $host,
            'mail.mailers.smtp.port'        => (int) ($values['mail_port'] ?? 587) ?: 587,
            'mail.mailers.smtp.username'    => ($values['mail_username'] ?? '') !== '' ? $values['mail_username'] : null,
            'mail.mailers.smtp.password'    => $password,
            'mail.mailers.smtp.scheme'      => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.require_tls' => $encryption === 'starttls',
            'mail.mailers.smtp.auto_tls'    => $encryption !== 'none',
        ]);

        if (! empty($values['mail_from_address'])) {
            config(['mail.from.address' => $values['mail_from_address'], 'mail.from.name' => $values['mail_from_name'] ?: config('mail.from.name')]);
        }

        // Bereits aufgebaute Mailer verwerfen, damit die neue Konfiguration greift.
        if (app()->resolved('mail.manager')) {
            Mail::purge('smtp');
        }
    }

    /** @return array<string, string> die gespeicherten mail_*-Werte (Passwort NICHT enthalten) */
    public static function mailForForm(): array
    {
        $values = Setting::whereIn('key', self::MAIL_KEYS)->pluck('value', 'key')->all();

        return [
            'host'        => $values['mail_host'] ?? '',
            'port'        => $values['mail_port'] ?? '587',
            'encryption'  => $values['mail_encryption'] ?? 'starttls',
            'username'    => $values['mail_username'] ?? '',
            'fromAddress' => $values['mail_from_address'] ?? '',
            'fromName'    => $values['mail_from_name'] ?? '',
            'hasPassword' => ! empty($values['mail_password']),
        ];
    }

    public static function storeMail(string $host, int $port, string $encryption, string $username, ?string $newPassword, string $fromAddress, string $fromName): void
    {
        Setting::set('mail_host', $host);
        Setting::set('mail_port', (string) $port);
        Setting::set('mail_encryption', $encryption);
        Setting::set('mail_username', $username);
        Setting::set('mail_from_address', $fromAddress);
        Setting::set('mail_from_name', $fromName);
        if ($newPassword !== null && $newPassword !== '') {
            Setting::set('mail_password', Crypt::encryptString($newPassword));
        }
        if ($username === '') {
            Setting::where('key', 'mail_password')->delete();
        }

        self::applyMail(Setting::whereIn('key', self::MAIL_KEYS)->pluck('value', 'key')->all());
    }

    /** Verwirft alle gespeicherten Mail-Einstellungen – danach gelten wieder die .env-Werte (nach dem nächsten Start der Anwendung). */
    public static function resetMail(): void
    {
        Setting::whereIn('key', self::MAIL_KEYS)->delete();
    }

    public static function decryptedPassword(): ?string
    {
        $stored = Setting::get('mail_password');
        if (! $stored) {
            return null;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return null;
        }
    }
}
