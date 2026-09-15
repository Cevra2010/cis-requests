<?php

namespace Modules\Lager\Services;

use Illuminate\Support\Facades\DB;
use Modules\Lager\Models\LagerPlacement;
use Modules\Lager\Models\LagerStock;
use Modules\Lager\Models\Lagerort;
use Modules\Wareneingang\Models\GoodsReceiptItem;

/**
 * Zentrale, einzige Schreibstelle für Lagerbestand – sowohl aus dem
 * Wareneingang heraus (receiveIntoStock, beide in der Planung beschriebenen
 * Buchungswege rufen dieselbe Methode auf) als auch für das spätere
 * Verschieben/Freigeben in der Warenübersicht. Bündelt die find-or-create-
 * Logik an einer Stelle, damit für eine Produkt/Lagerort/Projekt-Kombination
 * nie mehrere lager_stock-Zeilen parallel entstehen (bewusst kein DB-Unique-
 * Constraint, siehe Migration).
 */
class LagerStockService
{
    /**
     * Bucht eine empfangene Wareneingangsposition (teilweise oder vollständig)
     * in einen Lagerort ein. Das Projekt wird automatisch aus der Position der
     * Wareneingangsposition übernommen – keine separate Projektauswahl nötig.
     */
    public function receiveIntoStock(GoodsReceiptItem $item, Lagerort $lagerort, int $quantity): void
    {
        $productId = $item->position?->cis_row_id_product;
        if ($quantity <= 0 || ! $productId) {
            return;
        }

        $projectId = $item->position?->cis_row_id_project;

        DB::transaction(function () use ($item, $lagerort, $quantity, $productId, $projectId) {
            LagerPlacement::create([
                'cis_row_id_goods_receipt_item' => $item->cis_row_id,
                'cis_row_id_lagerort'           => $lagerort->cis_row_id,
                'quantity'                      => $quantity,
            ]);

            $this->incrementRow($productId, $lagerort->cis_row_id, $projectId, $quantity);
        });
    }

    /** Summe aus lager_placements – wie viel dieser Wareneingangsposition ist bereits eingelagert. */
    public function placedQuantity(string $goodsReceiptItemId): int
    {
        return (int) LagerPlacement::where('cis_row_id_goods_receipt_item', $goodsReceiptItemId)->sum('quantity');
    }

    /** Aufschlüsselung der bereits platzierten Menge je Lagerort (Anzeige "X in Lagerort eingebucht"). */
    public function placedByLagerort(string $goodsReceiptItemId): \Illuminate\Support\Collection
    {
        return LagerPlacement::where('cis_row_id_goods_receipt_item', $goodsReceiptItemId)
            ->selectRaw('cis_row_id_lagerort, SUM(quantity) as quantity')
            ->groupBy('cis_row_id_lagerort')
            ->havingRaw('SUM(quantity) > 0')
            ->get()
            ->map(fn ($row) => ['lagerort' => Lagerort::find($row->cis_row_id_lagerort), 'quantity' => (int) $row->quantity])
            ->filter(fn (array $entry) => $entry['lagerort'] !== null)
            ->values();
    }

    /**
     * Verschiebt eine Menge von einer Bestandszeile in einen anderen Lagerort
     * (gleiches Produkt/Projekt bleibt erhalten). Für Block-Verschiebung ruft
     * der Aufrufer diese Methode je ausgewählter Zeile auf.
     */
    public function move(string $stockId, string $targetLagerortId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($stockId, $targetLagerortId, $quantity) {
            $source = LagerStock::where('cis_row_id', $stockId)->lockForUpdate()->first();
            if (! $source || $source->cis_row_id_lagerort === $targetLagerortId) {
                return;
            }

            $moveQty = min($quantity, $source->quantity);
            if ($moveQty <= 0) {
                return;
            }

            $this->decrement($source, $moveQty);
            $this->incrementRow($source->cis_row_id_product, $targetLagerortId, $source->cis_row_id_project, $moveQty);
        });
    }

    /** Hebt die Projekt-Zuordnung einer Bestandszeile auf – wird wieder freier Bestand. */
    public function release(string $stockId): void
    {
        DB::transaction(function () use ($stockId) {
            $row = LagerStock::where('cis_row_id', $stockId)->lockForUpdate()->first();
            if (! $row || $row->cis_row_id_project === null) {
                return;
            }

            $this->decrement($row, $row->quantity);
            $this->incrementRow($row->cis_row_id_product, $row->cis_row_id_lagerort, null, $row->quantity);
        });
    }

    /** Summe des nicht einem Projekt zugeordneten (freien) Bestands eines Produkts, über alle Lagerorte hinweg. */
    public function freeQuantityFor(string $productId): int
    {
        return (int) LagerStock::where('cis_row_id_product', $productId)
            ->whereNull('cis_row_id_project')
            ->sum('quantity');
    }

    /**
     * Reserviert bis zu $quantity Stück freien (nicht zugeordneten) Bestands eines
     * Produkts für ein Projekt – bei Bedarf über mehrere Lagerorte verteilt, falls
     * an einem einzelnen nicht genug frei liegt. Gibt die tatsächlich reservierte
     * Menge zurück (kann kleiner als $quantity sein, wenn nicht genug frei ist).
     */
    public function reserveFreeStockForProject(string $productId, string $projectId, int $quantity): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($productId, $projectId, $quantity) {
            $freeRows = LagerStock::where('cis_row_id_product', $productId)
                ->whereNull('cis_row_id_project')
                ->where('quantity', '>', 0)
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            foreach ($freeRows as $row) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($remaining, $row->quantity);
                $this->decrement($row, $take);
                $this->incrementRow($productId, $row->cis_row_id_lagerort, $projectId, $take);
                $remaining -= $take;
            }

            return $quantity - $remaining;
        });
    }

    /**
     * Gegenstück zu reserveFreeStockForProject(): gibt bis zu $quantity Stück des für
     * ein Projekt reservierten Bestands eines Produkts wieder frei (nicht zugeordnet),
     * bei Bedarf über mehrere Lagerorte verteilt. Genutzt, wenn eine "aus Lager bezogen"
     * markierte Projektposition in der Menge reduziert wird.
     */
    public function releaseQuantityForProject(string $productId, string $projectId, int $quantity): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($productId, $projectId, $quantity) {
            $rows = LagerStock::where('cis_row_id_product', $productId)
                ->where('cis_row_id_project', $projectId)
                ->where('quantity', '>', 0)
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }
                $release = min($remaining, $row->quantity);
                $this->decrement($row, $release);
                $this->incrementRow($productId, $row->cis_row_id_lagerort, null, $release);
                $remaining -= $release;
            }

            return $quantity - $remaining;
        });
    }

    private function incrementRow(string $productId, string $lagerortId, ?string $projectId, int $quantity): void
    {
        $row = LagerStock::where('cis_row_id_product', $productId)
            ->where('cis_row_id_lagerort', $lagerortId)
            ->where('cis_row_id_project', $projectId)
            ->lockForUpdate()
            ->first();

        if ($row) {
            $row->increment('quantity', $quantity);
            return;
        }

        LagerStock::create([
            'cis_row_id_product'  => $productId,
            'cis_row_id_lagerort' => $lagerortId,
            'cis_row_id_project'  => $projectId,
            'quantity'            => $quantity,
        ]);
    }

    private function decrement(LagerStock $row, int $quantity): void
    {
        $remaining = $row->quantity - $quantity;
        if ($remaining <= 0) {
            $row->delete();
            return;
        }

        $row->update(['quantity' => $remaining]);
    }
}
