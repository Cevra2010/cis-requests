<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Datum/Uhrzeit, das Benutzer in Ortszeit (config app.display_timezone, Standard Europe/Berlin) eingeben und sehen,
 * obwohl die Anwendung intern in app.timezone (UTC) speichert. Wichtig für Zeitpunkte, die zeitgesteuert etwas auslösen
 * (z.B. Ausschreibungsbeginn/-ende): "20:22" meint 20:22 Uhr Ortszeit, nicht 20:22 UTC.
 */
class LocalDateTime implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value, config('app.timezone'))->setTimezone(config('app.display_timezone'));
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $value instanceof \DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value, config('app.display_timezone'));

        return $date->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}
