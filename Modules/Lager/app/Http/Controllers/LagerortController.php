<?php

namespace Modules\Lager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\DocumentNaming;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Modules\Lager\Models\Lagerort;
use Nwidart\Modules\Facades\Module;

class LagerortController extends Controller
{
    /**
     * Druckbares Etikett mit QR-Code für einen Lagerort. Der QR-Code kodiert
     * einen Link auf die "Scan"-Route (siehe routes/web.php) – wird er mit der
     * normalen Handykamera (außerhalb der App) gescannt, öffnet das direkt
     * "Ware buchen" für diesen Lagerort; innerhalb der App füllt der
     * eingebaute Scanner (siehe resources/js/qr-scanner.js) denselben Text
     * stattdessen nur in das gerade offene Lagerort-Auswahlfeld ein.
     */
    private function buildQr(Lagerort $lagerort)
    {
        return (new Builder(
            writer: new PngWriter(),
            data: route('lager.scan', $lagerort->cis_row_id),
            size: 400,
            margin: 16,
        ))->build();
    }

    /** QR-Code als PNG (Ansicht im Projekt-Tab "Lager"). */
    public function qr(string $lagerort)
    {
        $qrCode = $this->buildQr(Lagerort::findOrFail($lagerort));

        return response($qrCode->getString(), 200, ['Content-Type' => $qrCode->getMimeType()]);
    }

    public function labelPdf(string $lagerort)
    {
        $lagerort = Lagerort::findOrFail($lagerort);
        $qrCode   = $this->buildQr($lagerort);

        $branding = (Module::find('Branding')?->isEnabled())
            ? \Modules\Branding\Models\BrandingSetting::current()
            : null;

        $pdf = Pdf::loadView('lager::lagerort-label-pdf', [
            'lagerort'   => $lagerort,
            'qrDataUri'  => $qrCode->getDataUri(),
            'branding'   => $branding,
        ])->setPaper('a4', 'portrait');

        $filename = DocumentNaming::downloadFilename('Lagerort-Etikett - ' . $lagerort->name, 'pdf');

        return $pdf->stream($filename);
    }
}
