<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Solange kein einziges Benutzerkonto existiert (Neuinstallation oder nach
 * einem Werkseinstellungen-Reset), wird jede Anfrage zum Einrichtungs-Assistenten
 * umgeleitet. Der Assistent selbst ist von dieser Umleitung ausgenommen.
 */
class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        // Der Assistent selbst sowie Livewire's interne AJAX-/Asset-Endpunkte
        // (z.B. livewire/update) dürfen nie umgeleitet werden – sonst bekommt
        // Livewire statt der erwarteten JSON-Antwort eine HTML-Redirect-Seite.
        if ($request->is('Setup*') || $request->is('livewire*')) {
            return $next($request);
        }

        if (User::query()->doesntExist()) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
