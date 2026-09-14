<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\ChildPositionAward;
use App\Models\Offer;
use App\Models\OfferChildItem;
use App\Models\OfferItem;
use App\Models\PositionAward;
use App\Models\Project;
use App\Support\DocumentNaming;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Modules\Export\Services\ExportFileBuilder;
use Nwidart\Modules\Facades\Module;

class OfferController extends Controller
{
    public function exportOrderListPdf(string $project, string $offer)
    {
        [$p, $o, $rows] = $this->loadOrderList($project, $offer);

        $total    = $rows->sum('sum');
        $branding = (Module::find('Branding')?->isEnabled())
            ? \Modules\Branding\Models\BrandingSetting::current()
            : null;

        $pdf = Pdf::loadView('project.order-list-pdf', [
            'project'  => $p,
            'offer'    => $o,
            'rows'     => $rows,
            'total'    => $total,
            'branding' => $branding,
        ])->setPaper('a4', 'portrait');

        $filename = DocumentNaming::downloadFilename('Bestellliste - ' . $o->source->name, 'pdf');

        return $pdf->stream($filename);
    }

    /** Dieselbe Bestellliste wie exportOrderListPdf(), als CSV-/Excel-Tabelle statt PDF. */
    public function exportOrderListTable(string $project, string $offer, string $format, ExportFileBuilder $builder)
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        [$p, $o, $rows] = $this->loadOrderList($project, $offer);

        $headers = ['Pos.', 'Bezeichnung', 'Hinweis', 'Menge', 'Einzelpreis', 'Summe'];
        $tableRows = $rows->values()->map(fn ($row, $i) => [
            (string) ($i + 1),
            $row->name,
            (string) ($row->note ?? ''),
            (string) $row->qty,
            number_format($row->price, 2, ',', '.'),
            number_format($row->sum, 2, ',', '.'),
        ])->all();
        $tableRows[] = ['', '', '', '', 'Gesamtsumme', number_format($rows->sum('sum'), 2, ',', '.')];

        $content  = $builder->build($headers, $tableRows, $format, 'Bestellliste');
        $filename = DocumentNaming::downloadFilename('Bestellliste - ' . $o->source->name, $format);
        $mime     = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response($content, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** @return array{0: Project, 1: Offer, 2: Collection} */
    private function loadOrderList(string $project, string $offer): array
    {
        $p = Project::where('cis_row_id', $project)->firstOrFail();
        $o = Offer::where('cis_row_id', $offer)
            ->where('cis_row_id_project', $p->cis_row_id)
            ->with('source')
            ->firstOrFail();

        $awards = PositionAward::where('cis_row_id_offer', $o->cis_row_id)
            ->with(['position.product.childs'])
            ->get();

        $rows = $awards->map(function (PositionAward $award) use ($o) {
            $position = $award->position;
            $item     = OfferItem::where('cis_row_id_offer', $o->cis_row_id)
                ->where('cis_row_id_project_product', $position->cis_row_id)
                ->first();

            $qty   = $position->product_count;
            $price = $item?->price ?? 0;

            return (object) [
                'name'  => $position->product?->name ?? '–',
                'note'  => $position->note,
                'qty'   => $qty,
                'price' => $price,
                'sum'   => $price * $qty,
            ];
        })->values();

        // Unterprodukte erscheinen nur bei dem Anbieter, dem sie unter "Bestellung"
        // tatsächlich zugeordnet wurden (projektweit aggregierte Menge, unabhängig
        // davon, bei welchem Anbieter die zugehörigen Elternprodukte bestellt werden
        // – siehe ChildPositionAward). Der Preis ist der vom Anbieter tatsächlich
        // angebotene Unterprodukt-Preis (OfferChildItem), nicht der Katalogpreis.
        $awardedChildIds = ChildPositionAward::where('cis_row_id_project', $p->cis_row_id)
            ->where('cis_row_id_offer', $o->cis_row_id)
            ->pluck('cis_row_id_product');

        if ($awardedChildIds->isNotEmpty()) {
            $childPrices = OfferChildItem::where('cis_row_id_offer', $o->cis_row_id)
                ->whereIn('cis_row_id_product', $awardedChildIds)
                ->pluck('price', 'cis_row_id_product');

            foreach ($p->aggregatedChildPositions() as $childPosition) {
                $child = $childPosition['product'];
                if (! $awardedChildIds->contains($child->cis_row_id)) {
                    continue;
                }

                $qty   = $childPosition['quantity'];
                $price = (float) ($childPrices->get($child->cis_row_id) ?? 0.0);

                $rows->push((object) [
                    'name'  => $child->name,
                    'note'  => null,
                    'qty'   => $qty,
                    'price' => $price,
                    'sum'   => $price * $qty,
                ]);
            }
        }

        return [$p, $o, $rows];
    }
}
