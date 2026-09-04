<?php

namespace App\Http\Livewire;

use App\Models\UserPreference;
use App\Support\Changelog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Zwei Funktionen in einer Komponente:
 * 1) "Was ist neu"-Pflichthinweis: erscheint einmalig, wenn ein Benutzer eine
 *    neuere Version antrifft als die zuletzt von ihm bestätigte (in
 *    UserPreference unter "app.last_seen_version" abgelegt). Ein neuer
 *    Benutzer ohne bisherigen Eintrag bekommt keinen Hinweis (Historie
 *    irrelevant), nur die stille Ersteintragung.
 * 2) Die Versionsnummer im Sidebar-Footer, die jederzeit anklickbar die
 *    komplette Update-Historie zeigt (unabhängig vom "gesehen"-Status).
 */
class VersionNotice extends Component
{
    public string $currentVersion = '';
    public array $entries = [];
    public bool $show = false;
    public bool $showManual = false;

    private const PREFERENCE_KEY = 'app.last_seen_version';

    public function mount(): void
    {
        $this->currentVersion = Changelog::currentVersion();

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $seenVersion = UserPreference::get($user, self::PREFERENCE_KEY);

        if ($seenVersion === null) {
            // Neuer Benutzer: nichts anzeigen, nur still auf aktuellen Stand bringen.
            UserPreference::set($user, self::PREFERENCE_KEY, $this->currentVersion);
            return;
        }

        if (Changelog::compareVersions($this->currentVersion, $seenVersion) > 0) {
            $this->entries = Changelog::entriesSince($seenVersion);
            $this->show    = true;
        }
    }

    public function acknowledge(): void
    {
        if ($user = Auth::user()) {
            UserPreference::set($user, self::PREFERENCE_KEY, $this->currentVersion);
        }
        $this->show = false;
    }

    /** Manuelles Ansehen der kompletten Update-Historie über den Versions-Link im Sidebar-Footer. */
    public function openHistory(): void
    {
        $this->entries    = Changelog::allEntries();
        $this->showManual = true;
    }

    public function closeHistory(): void
    {
        $this->showManual = false;
    }

    public function render()
    {
        return view('livewire.version-notice');
    }
}
