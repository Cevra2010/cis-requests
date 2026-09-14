<?php

namespace Modules\Export\Services;

use App\Models\Category;
use App\Models\ProductSource;

/**
 * Zentrale Liste der Filtertypen, die sich einer Export-Vorlage frei
 * kombinierbar (UND-verknüpft, siehe ExportTemplateFilterMatcher) hinzufügen
 * lassen. Ein neuer Filtertyp braucht nur einen weiteren Eintrag hier plus
 * einen weiteren Fall im Matcher – der Rest (UI, Speicherung) ist generisch.
 */
class ExportFilterRegistry
{
    /** Mehrfachauswahl aus einer festen Werteliste (Kategorien, Quellen, ...). */
    public const TYPE_CATEGORY_MULTI = 'category_multi';
    public const TYPE_SOURCE_MULTI   = 'source_multi';

    /** Einfache Ja/Nein-Auswahl. */
    public const TYPE_BOOLEAN_SELECT = 'boolean_select';

    public const FILTERS = [
        'category'        => ['label' => 'Produktkategorie',       'type' => self::TYPE_CATEGORY_MULTI],
        'source'          => ['label' => 'Feste Produktquelle',    'type' => self::TYPE_SOURCE_MULTI],
        'tender_relevant' => ['label' => 'Ausschreibungsrelevant', 'type' => self::TYPE_BOOLEAN_SELECT],
        'is_set'          => ['label' => 'Set-Produkt',            'type' => self::TYPE_BOOLEAN_SELECT],
    ];

    /** Für boolean_select-Filter: einheitliche Ja/Nein-Optionen. */
    public const BOOLEAN_OPTIONS = ['yes' => 'Ja', 'no' => 'Nein'];

    public static function label(string $key): string
    {
        return self::FILTERS[$key]['label'] ?? $key;
    }

    public static function type(string $key): ?string
    {
        return self::FILTERS[$key]['type'] ?? null;
    }

    /**
     * Auswahloptionen für einen Filter, als id => label. Für boolean_select
     * immer Ja/Nein; für category_multi/source_multi die tatsächlich
     * vorhandenen Kategorien/Quellen.
     */
    public static function optionsFor(string $key): array
    {
        return match (self::type($key)) {
            self::TYPE_CATEGORY_MULTI => Category::ofType('product.category')
                ->orderBy('sort_order')->orderBy('name')->get()
                ->pluck('name', 'id')->all(),
            self::TYPE_SOURCE_MULTI => ProductSource::orderBy('name')->get()
                ->pluck('name', 'cis_row_id')->all(),
            self::TYPE_BOOLEAN_SELECT => self::BOOLEAN_OPTIONS,
            default => [],
        };
    }
}
