<?php

namespace App\Services;

use App\Models\ModuleLicense;
use Carbon\Carbon;

class LicenseService
{
    // ── Key generation (for internal tooling / console command) ───────────────

    public function generateKey(string $moduleName, string $licensee, ?string $expiresAt = null): string
    {
        $payload = [
            'module'   => $moduleName,
            'licensee' => $licensee,
            'issued'   => now()->toDateString(),
            'expires'  => $expiresAt,
        ];

        $encoded   = $this->base64url(json_encode($payload));
        $signature = $this->sign($encoded);

        return "{$encoded}.{$signature}";
    }

    // ── Validation ────────────────────────────────────────────────────────────

    /**
     * @return array{valid: bool, error?: string, licensee?: string, expires?: string|null, payload?: array}
     */
    public function validate(string $moduleName, string $key): array
    {
        $parts = explode('.', trim($key), 2);

        if (count($parts) !== 2) {
            return ['valid' => false, 'error' => 'Ungültiges Schlüsselformat.'];
        }

        [$encoded, $signature] = $parts;

        if (!hash_equals($this->sign($encoded), $signature)) {
            return ['valid' => false, 'error' => 'Ungültige Signatur — Schlüssel ist gefälscht oder beschädigt.'];
        }

        $payload = json_decode($this->base64urlDecode($encoded), true);

        if (!$payload || !isset($payload['module'], $payload['licensee'])) {
            return ['valid' => false, 'error' => 'Beschädigter Schlüssel.'];
        }

        if ($payload['module'] !== $moduleName) {
            return ['valid' => false, 'error' => "Dieser Schlüssel gilt für das Modul \"{$payload['module']}\", nicht für \"{$moduleName}\"."];
        }

        if (!empty($payload['expires']) && Carbon::parse($payload['expires'])->isPast()) {
            return ['valid' => false, 'error' => 'Lizenzschlüssel ist abgelaufen (gültig bis ' . $payload['expires'] . ').'];
        }

        return [
            'valid'    => true,
            'licensee' => $payload['licensee'],
            'issued'   => $payload['issued'] ?? null,
            'expires'  => $payload['expires'] ?? null,
            'payload'  => $payload,
        ];
    }

    // ── Activation ────────────────────────────────────────────────────────────

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

    private function secret(): string
    {
        return config('app.license_secret', config('app.key'));
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret());
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (strlen($data) + 3) % 4));
    }
}
