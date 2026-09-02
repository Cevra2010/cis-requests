<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Generischer Key-Value-Speicher für pro Benutzer gespeicherte Einstellungen
 * (z.B. Tabellen-Filter/Sortierung/Seitengröße, künftig auch andere UI-
 * Präferenzen). Bewusst nicht auf eine einzelne Funktion beschränkt – neue
 * Einstellungen bekommen einfach einen neuen, sprechenden Key
 * (Konvention für Tabellen: "table.<tableKey>.filters" / "...sort" / "...per_page").
 */
class UserPreference extends Model
{
    use CisUuid;

    protected $fillable = ['cis_row_id_user', 'key', 'value'];

    public static function get(User $user, string $key, $default = null)
    {
        $row = static::where('cis_row_id_user', $user->cis_row_id)
            ->where('key', $key)
            ->first();

        if (! $row || $row->value === null) {
            return $default;
        }

        $decoded = json_decode($row->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    public static function set(User $user, string $key, $value): void
    {
        static::updateOrCreate(
            ['cis_row_id_user' => $user->cis_row_id, 'key' => $key],
            ['value' => json_encode($value)]
        );
    }
}
