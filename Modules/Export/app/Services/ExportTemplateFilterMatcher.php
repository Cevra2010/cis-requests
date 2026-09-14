<?php

namespace Modules\Export\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Modules\Export\Models\ExportTemplateFilter;

/**
 * Prüft, ob ein Produkt alle Filter einer Export-Vorlage erfüllt (UND-
 * verknüpft – eine Vorlage ganz ohne Filter schließt nichts aus). Ergänzt
 * ExportFilterRegistry um die eigentliche Auswertung je Filtertyp.
 */
class ExportTemplateFilterMatcher
{
    public static function matches(?Product $product, Collection $filters): bool
    {
        if (! $product) {
            return false;
        }

        foreach ($filters as $filter) {
            if (! self::matchesOne($product, $filter)) {
                return false;
            }
        }

        return true;
    }

    private static function matchesOne(Product $product, ExportTemplateFilter $filter): bool
    {
        $values = (array) $filter->value;

        return match ($filter->field_key) {
            'category'        => in_array($product->category_id, $values, false),
            'source'          => in_array($product->cis_row_id_source, $values, true),
            'tender_relevant' => in_array($product->isTenderRelevant() ? 'yes' : 'no', $values, true),
            'is_set'          => in_array($product->isSet() ? 'yes' : 'no', $values, true),
            default           => true,
        };
    }
}
