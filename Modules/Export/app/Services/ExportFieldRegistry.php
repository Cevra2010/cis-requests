<?php

namespace Modules\Export\Services;

/**
 * Zentrale Liste der Datenfelder, die beim Erstellen einer Export-Vorlage
 * als Spalte ausgewählt werden können. Jedes Feld gibt an, in welchen
 * Vorlagen-Phasen (siehe ExportTemplate::PHASES) es sinnvoll ist – z.B.
 * ergeben leere Preisfelder zum Ausfüllen durch den Anbieter nur vor der
 * Ausschreibung Sinn, nicht mehr danach.
 */
class ExportFieldRegistry
{
    public const FIELDS = [
        'position_number'    => ['label' => 'Lfd. Nr.',                 'phases' => ['pre_tender', 'post_tender']],
        'product_name'       => ['label' => 'Bezeichnung',              'phases' => ['pre_tender', 'post_tender']],
        'quantity'           => ['label' => 'Menge',                    'phases' => ['pre_tender', 'post_tender']],
        'note'               => ['label' => 'Hinweis',                  'phases' => ['pre_tender', 'post_tender']],
        'description'        => ['label' => 'Beschreibungstext',        'phases' => ['pre_tender', 'post_tender']],
        'source_name'        => ['label' => 'Zugeordneter Anbieter',    'phases' => ['pre_tender', 'post_tender']],
        'tender_relevant'    => ['label' => 'Ausschreibungsrelevant',   'phases' => ['pre_tender', 'post_tender']],
        'unit_price'         => ['label' => 'Einzelpreis',              'phases' => ['pre_tender', 'post_tender']],
        'total_price'        => ['label' => 'Gesamtpreis',              'phases' => ['pre_tender', 'post_tender']],
        'vendor_unit_price'  => ['label' => 'Einzelpreis (Händler)',    'phases' => ['pre_tender']],
        'vendor_total_price' => ['label' => 'Gesamtpreis (Händler)',    'phases' => ['pre_tender']],
    ];

    /** Felder, die beim Export immer leer bleiben (vom Händler auszufüllen) und beim Wiedereinlesen als Angebotspreis übernommen werden. */
    public const VENDOR_PRICE_FIELDS = ['vendor_unit_price', 'vendor_total_price'];

    /**
     * Felder, nach denen eine Vorlage ihre Zeilen sortieren kann (siehe
     * TenderExporter::sortValue()). Unabhängig von der Spaltenauswahl – eine
     * Vorlage kann z.B. nach Kategorie sortieren, ohne die Kategorie als
     * eigene Spalte anzuzeigen. Gilt in beiden Phasen gleich.
     */
    public const SORTABLE_FIELDS = [
        'product_name' => 'Bezeichnung',
        'quantity'     => 'Menge',
        'category'     => 'Kategorie',
        'source_name'  => 'Anbieter/Quelle',
        'unit_price'   => 'Einzelpreis',
    ];

    public static function label(string $key): string
    {
        return self::FIELDS[$key]['label'] ?? $key;
    }

    /** Flaches key => label, beschränkt auf die für diese Phase sinnvollen Felder (z.B. für ein Auswahl-Dropdown). */
    public static function fieldsForPhase(string $phase): array
    {
        $result = [];
        foreach (self::FIELDS as $key => $field) {
            if (in_array($phase, $field['phases'], true)) {
                $result[$key] = $field['label'];
            }
        }

        return $result;
    }
}
