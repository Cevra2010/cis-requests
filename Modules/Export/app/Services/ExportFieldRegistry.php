<?php

namespace Modules\Export\Services;

/**
 * Zentrale Liste der Datenfelder, die beim Erstellen einer Export-Vorlage
 * als Spalte ausgewählt werden können.
 */
class ExportFieldRegistry
{
    public const FIELDS = [
        'position_number'    => 'Lfd. Nr.',
        'product_name'       => 'Bezeichnung',
        'quantity'           => 'Menge',
        'note'               => 'Hinweis',
        'description'        => 'Beschreibungstext',
        'source_name'        => 'Zugeordneter Anbieter',
        'unit_price'         => 'Einzelpreis',
        'total_price'        => 'Gesamtpreis',
        'vendor_unit_price'  => 'Einzelpreis (Händler)',
        'vendor_total_price' => 'Gesamtpreis (Händler)',
    ];

    /** Felder, die beim Export immer leer bleiben (vom Händler auszufüllen) und beim Wiedereinlesen als Angebotspreis übernommen werden. */
    public const VENDOR_PRICE_FIELDS = ['vendor_unit_price', 'vendor_total_price'];

    public static function label(string $key): string
    {
        return self::FIELDS[$key] ?? $key;
    }
}
