<?php

namespace Modules\Export\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Erzeugt aus Kopfzeilen/Zeilen eine fertige CSV- oder XLSX-Datei (als
 * String). Beide Formate laufen über PhpSpreadsheet, damit Formatierung
 * (Kopfzeile, Spaltenbreite) für beide konsistent ist.
 */
class ExportFileBuilder
{
    /**
     * @param array<int, string> $headers @param array<int, array<int, string>> $rows
     * @param int|null $hiddenColumnIndex 0-basierter Spaltenindex, der bei XLSX ausgeblendet wird
     *        (z.B. der technische Zeilenschlüssel für den Preis-Rückimport – CSV kennt kein
     *        Ausblenden, dort bleibt die Spalte sichtbar, aber am Ende der Zeile).
     */
    public function build(array $headers, array $rows, string $format, string $sheetTitle = 'Export', ?int $hiddenColumnIndex = null): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31) ?: 'Export');

        $sheet->fromArray($headers, null, 'A1');
        if (! empty($rows)) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $columnCount = max(count($headers), 1);
        $lastCol = Coordinate::stringFromColumnIndex($columnCount);

        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EEF2FF');

        for ($i = 1; $i <= $columnCount; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        if ($format === 'xlsx') {
            if ($hiddenColumnIndex !== null) {
                $hiddenCol = Coordinate::stringFromColumnIndex($hiddenColumnIndex + 1);
                $sheet->getColumnDimension($hiddenCol)->setVisible(false);
            }
            $writer = new Xlsx($spreadsheet);
        } else {
            $writer = new Csv($spreadsheet);
            $writer->setDelimiter(';');
            $writer->setEnclosure('"');
            $writer->setUseBOM(true);
        }

        ob_start();
        $writer->save('php://output');

        return ob_get_clean();
    }
}
