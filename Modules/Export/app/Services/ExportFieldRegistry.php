<?php

namespace Modules\Export\Services;

use Modules\Export\Models\ExportTemplate;

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
        'projects'         => 'Projekte',
        'users'            => 'Benutzer',
        'roles'            => 'Rollen',
        'groups'           => 'Gruppen',
        'lager'            => 'Lager',
        'products'         => 'Produkte',
        'categories'       => 'Kategorien',
        'sources'          => 'Quellen',
        'prices'           => 'Preise',
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

    /** Felder je Datengrundlage (siehe ExportTemplate::datasets()): Projekt-Positionen oder ein Systemdatensatz. */
    public static function fieldsOf(string $dataset): array
    {
        return $dataset === ExportTemplate::PROJECT_DATASET
            ? self::FIELDS
            : (Datasets\Datasets::find($dataset)?->fields() ?? []);
    }

    private static function definition(string $key): ?array
    {
        if (isset(self::FIELDS[$key])) {
            return self::FIELDS[$key];
        }
        foreach (Datasets\Datasets::all() as $dataset) {
            if (isset($dataset->fields()[$key])) {
                return $dataset->fields()[$key];
            }
        }

        return null;
    }

    public static function label(string $key): string
    {
        return self::definition($key)['label'] ?? $key;
    }

    public static function originLabel(string $key): string
    {
        if ($key === self::FREE_FIELD_KEY) {
            return self::FREE_FIELD_ORIGIN_LABEL;
        }

        return self::ORIGINS[self::definition($key)['origin'] ?? ''] ?? '';
    }

    /** Auswahlliste für Vorlagen auf einem Systemdatensatz (kein Phasen-Filter). Gleiche Struktur wie optionsForPhase(). */
    public static function optionsForDataset(string $dataset): array
    {
        $options = [];
        foreach (self::ORIGINS as $originKey => $originLabel) {
            foreach (self::fieldsOf($dataset) as $key => $field) {
                if ($field['origin'] === $originKey) {
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
