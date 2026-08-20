<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;

/**
 * Bereitet den Preisverlauf eines Produkts für die Darstellung als Liniendiagramm
 * auf: eine Durchschnitts-Serie (über alle Lieferanten) sowie eine Serie je
 * Lieferant.
 */
class PriceHistoryService
{
    public static function buildChartData(Product $product): array
    {
        $history = $product->prices()->with('source')->orderBy('created_at')->get();

        if ($history->count() < 2) {
            return ['has_data' => false];
        }

        // ── Serie je Lieferant ───────────────────────────────────────────────
        $bySource = [];
        foreach ($history as $price) {
            $sourceName = $price->source?->name ?? 'Ohne Lieferant';
            $bySource[$sourceName] ??= [];
            $bySource[$sourceName][] = ['date' => $price->created_at, 'amount' => (float) $price->amount];
        }

        // ── Durchschnitts-Serie: bei jeder Preisänderung neu berechnet aus dem
        //    jeweils zuletzt bekannten Preis je Lieferant ──────────────────────
        $lastKnown = [];
        $average   = [];
        foreach ($history as $price) {
            $sourceName             = $price->source?->name ?? 'Ohne Lieferant';
            $lastKnown[$sourceName] = (float) $price->amount;
            $average[] = [
                'date'   => $price->created_at,
                'amount' => array_sum($lastKnown) / count($lastKnown),
            ];
        }

        $allAmounts = $history->pluck('amount')->map(fn ($a) => (float) $a);

        return [
            'has_data' => true,
            'average'  => $average,
            'sources'  => $bySource,
            'min'      => (float) $allAmounts->min(),
            'max'      => (float) $allAmounts->max(),
            'min_date' => $history->first()->created_at,
            'max_date' => $history->last()->created_at,
        ];
    }
}
