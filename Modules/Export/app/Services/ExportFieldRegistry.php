<?php

namespace Modules\Export\Services;

/**
 * Zentrale Liste der Datenfelder, die beim Erstellen einer Export-Vorlage
 * als Spalte ausgewählt werden können. Jedes Feld gibt an, in welchen
 * Vorlagen-Phasen (siehe ExportTemplate::PHASES) es sinnvoll ist – z.B.
 * ergeben leere Preisfelder zum Ausfüllen durch den Anbieter nur vor der
 * Ausschreibung Sinn, nicht mehr danach.
 *
 * Jedes Feld hat zusätzlich eine Herkunft ('origin', siehe ORIGINS): aus
 * welchem Datenbereich es stammt. Die Feldauswahl in der Vorlagen-Verwaltung
 * gruppiert und filtert danach – weitere Herkünfte (Benutzer, Gruppen, Rollen
 * …) kommen hinzu, indem sie hier in ORIGINS und FIELDS ergänzt werden.
 */
class ExportFieldRegistry
{
    /** Herkunft der Felder (Datenbereich), in Anzeigereihenfolge. */
    public const ORIGINS = [
        'project_material' => 'Projekte (Materialausschreibung)',
    ];

    /** Herkunft des Freifelds (fester Text/leere Spalte) – an keinen Datenbereich gebunden. */
    public const FREE_FIELD_KEY    = 'static_text';
    public const FREE_FIELD_ORIGIN = 'general';
    public const FREE_FIELD_ORIGIN_LABEL = 'Allgemein';

    public const FIELDS = [
        'position_number'    => ['label' => 'Lfd. Nr.',                 'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'product_name'       => ['label' => 'Bezeichnung',              'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'quantity'           => ['label' => 'Menge',                    'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'note'               => ['label' => 'Hinweis',                  'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'description'        => ['label' => 'Beschreibungstext',        'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'source_name'        => ['label' => 'Zugeordneter Anbieter',    'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'tender_relevant'    => ['label' => 'Ausschreibungsrelevant',   'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'unit_price'         => ['label' => 'Einzelpreis',              'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'total_price'        => ['label' => 'Gesamtpreis',              'origin' => 'project_material', 'phases' => ['pre_tender', 'post_tender']],
        'vendor_unit_price'  => ['label' => 'Einzelpreis (Händler)',    'origin' => 'project_material', 'phases' => ['pre_tender']],
        'vendor_total_price' => ['label' => 'Gesamtpreis (Händler)',    'origin' => 'project_material', 'phases' => ['pre_tender']],
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

    public static function originLabel(string $key): string
    {
        if ($key === self::FREE_FIELD_KEY) {
            return self::FREE_FIELD_ORIGIN_LABEL;
        }

        return self::ORIGINS[self::FIELDS[$key]['origin'] ?? ''] ?? '';
    }

    /**
     * Auswahlliste für das Feld-Dropdown der Vorlagen-Verwaltung (Phase-gefiltert),
     * jedes Feld mit Herkunft; das Freifeld steht unter "Allgemein".
     *
     * @return array<int, array{key: string, label: string, origin: string, originLabel: string}>
     */
    public static function optionsForPhase(string $phase): array
    {
        $options = [];
        foreach (self::ORIGINS as $originKey => $originLabel) {
            foreach (self::FIELDS as $key => $field) {
                if ($field['origin'] === $originKey && in_array($phase, $field['phases'], true)) {
                    $options[] = ['key' => $key, 'label' => $field['label'], 'origin' => $originKey, 'originLabel' => $originLabel];
                }
            }
        }

        $options[] = [
            'key'         => self::FREE_FIELD_KEY,
            'label'       => 'Freifeld (fester Text oder leer)',
            'origin'      => self::FREE_FIELD_ORIGIN,
            'originLabel' => self::FREE_FIELD_ORIGIN_LABEL,
        ];

        return $options;
    }
}
