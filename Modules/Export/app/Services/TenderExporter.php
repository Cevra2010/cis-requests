<?php

namespace Modules\Export\Services;

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectProduct;
use App\Services\ChildProductAggregator;
use Illuminate\Support\Facades\DB;
use Modules\Export\Models\ExportTemplate;

/**
 * Löst eine Export-Vorlage gegen die Positionen eines Projekts auf und
 * liefert reine Kopfzeilen/Zeilen-Arrays – unabhängig vom Ausgabeformat
 * (CSV/XLSX), siehe ExportFileBuilder.
 *
 * Unterprodukte erscheinen als eigenständige Zeilen (nicht nur eingerückt
 * unter ihrem Elternprodukt) und werden – falls sie bei mehreren Positionen
 * als Unterprodukt hinterlegt sind – über das gesamte Projekt hinweg zu
 * einer einzigen Zeile mit Gesamtmenge zusammengefasst.
 */
class TenderExporter
{
    /** @return array{headers: array<int, string>, rows: array<int, array<int, string>>} */
    public function build(Project $project, ExportTemplate $template): array
    {
        $columns = $template->columns;
        $headers = $columns->pluck('label')->all();

        $positions = $project->positions()
            ->with(['product.childs', 'award.offer.source', 'offerItems'])
            ->get();

        $rows  = [];
        $index = 0;

        foreach ($positions as $position) {
            $index++;
            $unitPrice = $this->unitPrice($position);

            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->resolveField($column->field_key, $position, $index, $unitPrice);
            }
            $rows[] = $row;
        }

        $childTotals = ChildProductAggregator::aggregate(
            $positions->map(fn (ProjectProduct $p) => ['product' => $p->product, 'quantity' => $p->product_count])
        );

        foreach ($childTotals as $entry) {
            $index++;
            $child     = $entry['product'];
            $quantity  = $entry['quantity'];
            $unitPrice = $child->price()?->amount !== null ? (float) $child->price()->amount : null;

            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->resolveChildField($column->field_key, $child, $quantity, $index, $unitPrice);
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function resolveField(string $key, ProjectProduct $position, int $number, ?float $unitPrice): string
    {
        return match ($key) {
            'position_number' => (string) $number,
            'product_name'    => $position->product?->name ?? '',
            'quantity'        => (string) $position->product_count,
            'note'            => (string) ($position->note ?? ''),
            'description'     => $position->product ? $this->description($position->product->cis_row_id) : '',
            'source_name'     => $position->award?->offer?->source?->name ?? '',
            'unit_price'      => $unitPrice !== null ? number_format($unitPrice, 2, ',', '.') : '',
            'total_price'     => $unitPrice !== null ? number_format($unitPrice * $position->product_count, 2, ',', '.') : '',
            default           => '',
        };
    }

    private function resolveChildField(string $key, Product $child, int $quantity, int $number, ?float $unitPrice): string
    {
        return match ($key) {
            'position_number' => (string) $number,
            'product_name'    => $child->name,
            'quantity'        => (string) $quantity,
            'note'            => '',
            'description'     => $this->description($child->cis_row_id),
            'source_name'     => '',
            'unit_price'      => $unitPrice !== null ? number_format($unitPrice, 2, ',', '.') : '',
            'total_price'     => $unitPrice !== null ? number_format($unitPrice * $quantity, 2, ',', '.') : '',
            default           => '',
        };
    }

    private function description(string $productId): string
    {
        $desc = DB::table('product_descriptions')
            ->where('cis_row_id_product', $productId)
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
