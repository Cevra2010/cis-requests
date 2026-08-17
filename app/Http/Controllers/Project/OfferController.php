<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\PositionAward;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Nwidart\Modules\Facades\Module;

class OfferController extends Controller
{
    public function exportOrderListPdf(string $project, string $offer)
    {
        $p = Project::where('cis_row_id', $project)->firstOrFail();
        $o = Offer::where('cis_row_id', $offer)
            ->where('cis_row_id_project', $p->cis_row_id)
            ->with('source')
            ->firstOrFail();

        $awards = PositionAward::where('cis_row_id_offer', $o->cis_row_id)
            ->with(['position.product'])
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

        $filename = str($p->name . '-bestellliste-' . $o->source->name)->slug() . '.pdf';

        return $pdf->stream($filename);
    }
}
