<?php

namespace Modules\Export\Services;

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectProduct;
use App\Services\ChildProductAggregator;
use Illuminate\Support\Facades\DB;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Models\ExportTemplateColumn;

/**
 * Löst eine Export-Vorlage gegen die Positionen eines Projekts auf und
 * liefert reine Kopfzeilen/Zeilen-Arrays – unabhängig vom Ausgabeformat
 * (CSV/XLSX), siehe ExportFileBuilder.
 *
 * Unterprodukte erscheinen als eigenständige Zeilen (nicht nur eingerückt
 * unter ihrem Elternprodukt) und werden – falls sie bei mehreren Positionen
 * als Unterprodukt hinterlegt sind – über das gesamte Projekt hinweg zu
 * einer einzigen Zeile mit Gesamtmenge zusammengefasst. Der Preis stammt aus
 * Project::effectivePrice() – bei fixierten Projekten also aus dem
 * eingefrorenen Preis, nicht dem aktuellen Katalogpreis.
 */
class TenderExporter
{
    /**
     * Technischer Spaltenname für den unsichtbaren Zeilenschlüssel, der beim
     * Rückimport der Händlerpreise (siehe OfferComparison::importOfferFile())
     * jede Zeile wieder eindeutig einer Position/einem Unterprodukt zuordnet
     * – unabhängig von Zeilenreihenfolge oder umbenannten Spaltentiteln.
     * Format: "P:{cis_row_id_project_product}" für Hauptpositionen,
     * "C:{cis_row_id_product}" für aggregierte Unterprodukt-Zeilen.
     */
    public const IMPORT_KEY_HEADER = '_ImportKey';

    /** @return array{headers: array<int, string>, rows: array<int, array<int, string>>, import_key_index: ?int} */
    public function build(Project $project, ExportTemplate $template): array
    {
        $columns          = $template->columns;
        $headers          = $columns->pluck('label')->all();
        $includeImportKey = $template->hasVendorPriceColumn();
        $importKeyIndex   = $includeImportKey ? count($headers) : null;

        if ($includeImportKey) {
            $headers[] = self::IMPORT_KEY_HEADER;
        }

        // Hausinterne Positionen (bereits im Haus vorhanden, siehe
        // ProjectProduct::is_internal) werden nicht ausgeschrieben und fehlen
        // daher komplett im Export – inklusive ihrer Unterprodukte.
        $positions = $project->positions()
            ->with(['product.childs', 'award.offer.source', 'offerItems'])
            ->get()
            ->reject(fn (ProjectProduct $p) => $p->is_internal);

        $rows  = [];
        $index = 0;

        // Setprodukte erscheinen nie als eigene Position (siehe Product::isSet())
        // – nur ihre Mitgliedsprodukte, die über ChildProductAggregator unten
        // ohnehin projektweit erfasst werden (die Aggregation braucht daher
        // weiterhin die VOLLE $positions-Liste, inklusive Sets).
        foreach ($positions as $position) {
            if ($position->product?->isSet()) {
                continue;
            }

            $index++;
            $unitPrice = $this->unitPrice($position);

            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->resolveField($column, $position, $index, $unitPrice, $project->cis_row_id);
            }
            if ($includeImportKey) {
                $row[] = 'P:' . $position->cis_row_id;
            }
            $rows[] = $row;
        }

        $childTotals = ChildProductAggregator::aggregate(
            $positions->map(fn (ProjectProduct $p) => ['product' => $p->product, 'quantity' => $p->product_count])
        )->sortBy(fn ($entry) => $entry['product']->name)->values();

        foreach ($childTotals as $entry) {
            $index++;
            $child     = $entry['product'];
            $quantity  = $entry['quantity'];
            $unitPrice = $project->effectivePrice($child);

            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->resolveChildField($column, $child, $quantity, $index, $unitPrice, $project->cis_row_id);
            }
            if ($includeImportKey) {
                $row[] = 'C:' . $child->cis_row_id;
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows, 'import_key_index' => $importKeyIndex];
    }

    private function resolveField(ExportTemplateColumn $column, ProjectProduct $position, int $number, ?float $unitPrice, string $projectId): string
    {
        if ($column->isFreeField()) {
            return $column->static_value ?? '';
        }

        return match ($column->field_key) {
            'position_number' => (string) $number,
            'product_name'    => $position->product?->name ?? '',
            'quantity'        => (string) $position->product_count,
            'note'            => (string) ($position->note ?? ''),
            'description'     => $position->product ? $this->description($position->product->cis_row_id, $projectId) : '',
            'source_name'     => $position->award?->offer?->source?->name ?? '',
            'unit_price'      => $unitPrice !== null ? number_format($unitPrice, 2, ',', '.') : '',
            'total_price'     => $unitPrice !== null ? number_format($unitPrice * $position->product_count, 2, ',', '.') : '',
            default           => '',
        };
    }

    private function resolveChildField(ExportTemplateColumn $column, Product $child, int $quantity, int $number, ?float $unitPrice, string $projectId): string
    {
        if ($column->isFreeField()) {
            return $column->static_value ?? '';
        }

        return match ($column->field_key) {
            'position_number' => (string) $number,
            'product_name'    => $child->name,
            'quantity'        => (string) $quantity,
            'note'            => '',
            'description'     => $this->description($child->cis_row_id, $projectId),
            'source_name'     => '',
            'unit_price'      => $unitPrice !== null ? number_format($unitPrice, 2, ',', '.') : '',
            'total_price'     => $unitPrice !== null ? number_format($unitPrice * $quantity, 2, ',', '.') : '',
            default           => '',
        };
    }

    /** Projektspezifischer Ausschreibungstext, mit Fallback auf den globalen Standardtext des Produkts. */
    private function description(string $productId, string $projectId): string
    {
        $desc = DB::table('product_descriptions')
            ->where('cis_row_id_product', $productId)
            ->where('cis_row_id_project', $projectId)
            ->whereNull('deleted_at')
            ->first()
            ?? DB::table('product_descriptions')
                ->where('cis_row_id_product', $productId)
                ->whereNull('cis_row_id_project')
                ->whereNull('deleted_at')
                ->first();

        return $desc?->text ?? '';
    }

    private function unitPrice(ProjectProduct $position): ?float
    {
        $offerId = $position->award?->cis_row_id_offer;
        if (! $offerId) {
            return null;
        }

        $item = $position->offerItems->firstWhere('cis_row_id_offer', $offerId);

        return $item?->price !== null ? (float) $item->price : null;
    }
}
