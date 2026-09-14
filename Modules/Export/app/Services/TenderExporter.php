<?php

namespace Modules\Export\Services;

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectProduct;
use App\Services\ChildProductAggregator;
use Illuminate\Support\Collection;
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
        $template->loadMissing('filters');

        $columns          = $template->columns;
        $headers          = $columns->pluck('label')->all();
        $includeImportKey = $template->hasVendorPriceColumn();
        $importKeyIndex   = $includeImportKey ? count($headers) : null;

        if ($includeImportKey) {
            $headers[] = self::IMPORT_KEY_HEADER;
        }

        // Welche Positionen überhaupt in den Export gelangen, entscheiden die
        // frei kombinierbaren Filter der Vorlage (siehe ExportFilterRegistry /
        // ExportTemplateFilterMatcher) – eine Vorlage ganz ohne Filter schließt
        // nichts aus. Aus Lagerbestand bezogene Positionen (sourced_from_stock,
        // Modul "Lager") werden unabhängig von den Filtern nie exportiert – für
        // sie wird nichts beschafft, sie gehören in keine Tabelle.
        $positions = $project->positions()
            ->with(['product.category', 'product.childs.category', 'product.childs.source', 'product.source', 'award.offer.source', 'offerItems'])
            ->get()
            ->reject(fn (ProjectProduct $p) => $p->sourced_from_stock || ! ExportTemplateFilterMatcher::matches($p->product, $template->filters));

        if ($template->sort_field) {
            $positions = $this->sorted(
                $positions,
                fn (ProjectProduct $p) => $this->sortValue($p->product, $p->product_count, $this->unitPrice($p), $template->sort_field),
                $template->sort_direction
            );
        }

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
        )->values();

        $childTotals = $template->sort_field
            ? $this->sorted(
                $childTotals,
                fn (array $entry) => $this->sortValue($entry['product'], $entry['quantity'], $project->effectivePrice($entry['product']), $template->sort_field),
                $template->sort_direction
            )
            : $childTotals->sortBy(fn (array $entry) => $entry['product']->name)->values();

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
            // Kein Angebot vorhanden (nicht-ausschreibungsrelevante Position) → feste Quelle des Produkts stattdessen.
            'source_name'     => $position->award?->offer?->source?->name ?? $position->product?->source?->name ?? '',
            'tender_relevant' => $position->product?->isTenderRelevant() ? 'Ja' : 'Nein',
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
            'source_name'     => $child->source?->name ?? '',
            'tender_relevant' => $child->isTenderRelevant() ? 'Ja' : 'Nein',
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

    /** @param Collection<int, mixed> $entries */
    private function sorted(Collection $entries, \Closure $keyFn, string $direction): Collection
    {
        return ($direction === 'desc' ? $entries->sortByDesc($keyFn) : $entries->sortBy($keyFn))->values();
    }

    /** Vergleichswert für die Sortierung, je nach gewähltem Sortierfeld (siehe ExportTemplate::$sort_field). */
    private function sortValue(?Product $product, int $quantity, ?float $unitPrice, string $field): mixed
    {
        return match ($field) {
            'product_name' => mb_strtolower($product?->name ?? ''),
            'quantity'     => $quantity,
            'category'     => mb_strtolower($product?->category?->name ?? ''),
            'source_name'  => mb_strtolower($product?->source?->name ?? ''),
            'unit_price'   => $unitPrice ?? 0.0,
            default        => 0,
        };
    }
}
