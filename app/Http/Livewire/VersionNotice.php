<?php

namespace App\Http\Livewire;

use App\Models\UserPreference;
use App\Support\Changelog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * "Was ist neu"-Hinweis: erscheint einmalig, wenn ein Benutzer eine neuere
 * Version antrifft als die zuletzt von ihm bestätigte (in UserPreference
 * unter dem Key "app.last_seen_version" abgelegt). Ein neuer Benutzer, der
 * noch nie eine Version gesehen hat, bekommt keinen Hinweis (die Historie
 * ist für einen frischen Account irrelevant) – die aktuelle Version wird
 * für ihn nur still hinterlegt.
 */
class VersionNotice extends Component
{
    public string $currentVersion = '';
    public array $entries = [];
    public bool $show = false;

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

    public function render()
    {
        return view('livewire.version-notice');
    }
}
