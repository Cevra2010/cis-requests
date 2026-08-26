<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Fasst Unterprodukte über mehrere Positionen hinweg zusammen. Ein Unterprodukt
 * kann mehreren Elternprodukten zugeordnet sein (z.B. "Übergangsstück" sowohl bei
 * "Strahlrohr" als auch bei "Verteiler") – hier wird je Unterprodukt die
 * Gesamtmenge über alle Positionen aufsummiert, damit es im Export/Bestellliste
 * nur einmal als eigenständige Zeile erscheint.
 */
class ChildProductAggregator
{
    /**
     * @param  iterable<array{product: ?Product, quantity: int}>  $items  Positionen mit Menge (Eltern-Produkt muss `childs` geladen haben)
     * @return Collection<int, array{product: Product, quantity: int, from_set_only: bool}>
     */
    public static function aggregate(iterable $items): Collection
    {
        $totals = [];

        foreach ($items as $item) {
            $product  = $item['product'] ?? null;
            $quantity = $item['quantity'] ?? 0;

            if (! $product || $quantity <= 0) {
                continue;
            }

            $parentIsSet = $product->isSet();

            foreach ($product->childs as $child) {
                $id = $child->cis_row_id;

                if (! isset($totals[$id])) {
                    // Solange JEDES bisher gesehene Elternprodukt ein Set ist, gilt das
                    // Kind als "nur aus Sets stammend" – dann wird es wie eine normale,
                    // eigenständige Position behandelt statt wie ein echtes Unterprodukt
                    // (siehe Verwendung in OfferComparison/AwardManager/TenderExporter).
                    $totals[$id] = ['product' => $child, 'quantity' => 0, 'from_set_only' => true];
                }

                $totals[$id]['quantity'] += $quantity;
                if (! $parentIsSet) {
                    $totals[$id]['from_set_only'] = false;
                }
            }
        }

        return collect($totals)->values();
    }
}
