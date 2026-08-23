<?php

namespace App\Services;

use App\Models\ModuleLicense;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * Zwei Arten signierter Lizenzschlüssel:
 *
 * - "Master"-Schlüssel: eine Firmenlizenz für die gesamte Installation, trägt
 *   eine frisch generierte master_id. Wird einmalig im Einrichtungs-Assistenten
 *   hinterlegt (siehe installationMasterId()).
 * - Modul-Schlüssel: gilt nur für ein bestimmtes Modul UND nur für die
 *   master_id, für die er ausgestellt wurde. So kann eine Modul-Lizenz nicht
 *   an eine andere Firma/Installation weitergegeben werden – sie validiert
 *   nur, wenn die master_id im Schlüssel zur master_id dieser Installation passt.
 *
 * Diese Klasse PRÜFT Lizenzschlüssel nur noch (Ed25519, öffentlicher Schlüssel
 * in config/licensing.php). Die Erzeugung von Schlüsseln lebt jetzt
 * ausschließlich im separaten Tool "cis-requests-license", das den privaten
 * Signierschlüssel hält.
 */
class LicenseService
{
    // ── Master-Lizenz (Firma/Installation) ────────────────────────────────────

    /** @return array{valid: bool, error?: string, master_id?: string, licensee?: string, expires?: string|null, payload?: array} */
    public function validateMasterKey(string $key): array
    {
        $decoded = $this->decodeAndVerify($key);
        if (! $decoded['valid']) {
            return $decoded;
        }
        $payload = $decoded['payload'];

        if (($payload['type'] ?? null) !== 'master' || ! isset($payload['master_id'], $payload['licensee'])) {
            return ['valid' => false, 'error' => 'Dies ist kein gültiger Firmenlizenzschlüssel.'];
        }

        if (! empty($payload['expires']) && Carbon::parse($payload['expires'])->isPast()) {
            return ['valid' => false, 'error' => 'Firmenlizenz ist abgelaufen (gültig bis ' . $payload['expires'] . ').'];
        }

        return [
            'valid'     => true,
            'master_id' => $payload['master_id'],
            'licensee'  => $payload['licensee'],
            'issued'    => $payload['issued'] ?? null,
            'expires'   => $payload['expires'] ?? null,
            'payload'   => $payload,
        ];
    }

    /** Validiert die Firmenlizenz und hinterlegt sie als Identität dieser Installation. */
    public function activateMaster(string $key): array
    {
        $result = $this->validateMasterKey($key);
        if (! $result['valid']) {
            return $result;
        }

        Setting::set('license_master_id', $result['master_id']);
        Setting::set('license_master_licensee', $result['licensee']);
        Setting::set('license_master_expires_at', $result['expires']);
        Setting::set('license_master_key', $key);

        return $result;
    }

    public function installationMasterId(): ?string
    {
        return Setting::get('license_master_id');
    }

    public function installationLicensee(): ?string
    {
        return Setting::get('license_master_licensee');
    }

    public function hasMasterLicense(): bool
    {
        if (! $this->installationMasterId()) {
            return false;
        }

        $expires = Setting::get('license_master_expires_at');

        return ! ($expires && Carbon::parse($expires)->isPast());
    }

    // ── Modul-Lizenzen ──────────────────────────────────────────────────────────

    /**
     * @return array{valid: bool, error?: string, licensee?: string, expires?: string|null, payload?: array}
     */
    public function validate(string $moduleName, string $key): array
    {
        $decoded = $this->decodeAndVerify($key);
        if (! $decoded['valid']) {
            return $decoded;
        }
        $payload = $decoded['payload'];

        if (($payload['type'] ?? null) !== 'module' || ! isset($payload['module'], $payload['licensee'])) {
            return ['valid' => false, 'error' => 'Dies ist kein gültiger Modul-Lizenzschlüssel.'];
        }

        if ($payload['module'] !== $moduleName) {
            return ['valid' => false, 'error' => "Dieser Schlüssel gilt für das Modul \"{$payload['module']}\", nicht für \"{$moduleName}\"."];
        }

        if (! empty($payload['expires']) && Carbon::parse($payload['expires'])->isPast()) {
            return ['valid' => false, 'error' => 'Lizenzschlüssel ist abgelaufen (gültig bis ' . $payload['expires'] . ').'];
        }

        $installationMasterId = $this->installationMasterId();
        if (! $installationMasterId) {
            return ['valid' => false, 'error' => 'Für diese Installation ist noch keine Firmenlizenz aktiviert. Bitte zuerst die Firmenlizenz einrichten.'];
        }

        if (($payload['master_id'] ?? null) !== $installationMasterId) {
            return ['valid' => false, 'error' => 'Dieser Lizenzschlüssel wurde für eine andere Firma/Installation ausgestellt und ist hier nicht gültig.'];
        }

        return [
            'valid'    => true,
            'licensee' => $payload['licensee'],
            'issued'   => $payload['issued'] ?? null,
            'expires'  => $payload['expires'] ?? null,
            'payload'  => $payload,
        ];
    }

    // ── Aktivierung ────────────────────────────────────────────────────────────

    public function activate(string $moduleName, string $key): array
    {
        $result = $this->validate($moduleName, $key);

        if (!$result['valid']) {
            return $result;
        }

        ModuleLicense::updateOrCreate(
            ['module_name' => $moduleName],
            [
                'license_key' => $key,
                'licensee'    => $result['licensee'],
                'issued_at'   => $result['issued'] ?? null,
                'expires_at'  => $result['expires'] ?? null,
                'payload'     => $result['payload'],
            ]
        );

        return $result;
    }

    // ── Query ─────────────────────────────────────────────────────────────────

    public function forModule(string $moduleName): ?ModuleLicense
    {
        return ModuleLicense::where('module_name', $moduleName)->first();
    }

    public function isLicensed(string $moduleName): bool
    {
        $license = $this->forModule($moduleName);
        return $license !== null && $license->isValid();
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function decodeAndVerify(string $key): array
    {
        $parts = explode('.', trim($key), 2);

        if (count($parts) !== 2) {
            return ['valid' => false, 'error' => 'Ungültiges Schlüsselformat.'];
        }

        [$encoded, $signature] = $parts;

        if (! $this->verifySignature($encoded, $signature)) {
            return ['valid' => false, 'error' => 'Ungültige Signatur — Schlüssel ist gefälscht oder beschädigt.'];
        }

        $payload = json_decode($this->base64urlDecode($encoded), true);

        if (!$payload) {
            return ['valid' => false, 'error' => 'Beschädigter Schlüssel.'];
        }

        return ['valid' => true, 'payload' => $payload];
    }

    /**
     * Ed25519-Prüfung (libsodium) mit dem öffentlichen Schlüssel aus
     * config/licensing.php. Funktioniert unabhängig von APP_KEY und ohne
     * Zugriff auf den privaten Signierschlüssel (der lebt nur im separaten
     * Tool "cis-requests-license").
     */
    private function verifySignature(string $data, string $signature): bool
    {
        $publicKey = base64_decode(config('licensing.public_key'));
        $decodedSignature = $this->base64urlDecode($signature);

        if (strlen($decodedSignature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($decodedSignature, $data, $publicKey);
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (strlen($data) + 3) % 4));
    }
}
