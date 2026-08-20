<?php

namespace Modules\Export\Services;

/**
 * Zentrale Liste der Datenfelder, die beim Erstellen einer Export-Vorlage
 * als Spalte ausgewählt werden können.
 */
class ExportFieldRegistry
{
    public const FIELDS = [
        'position_number' => 'Lfd. Nr.',
        'product_name'    => 'Bezeichnung',
        'quantity'        => 'Menge',
        'note'            => 'Hinweis',
        'description'     => 'Beschreibungstext',
        'source_name'     => 'Zugeordneter Anbieter',
        'unit_price'      => 'Einzelpreis',
        'total_price'     => 'Gesamtpreis',
    ];

    public static function label(string $key): string
    {
        return self::FIELDS[$key] ?? $key;
    }
}
