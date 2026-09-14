<?php

namespace App\Support;

/**
 * Einheitliche Benennung für alle im Dokumentenmanager sichtbaren Dokumente
 * (automatisch erzeugte wie hochgeladene). Bewusst ohne Projektnamen im
 * Dateinamen – der Projektkontext ist im Dokumentenmanager ohnehin klar
 * (man befindet sich bereits im jeweiligen Projekt), ein zusätzlicher
 * Projektname in jedem einzelnen Dateinamen machte die Namen unnötig lang.
 * Beim tatsächlichen Herunterladen kommt zusätzlich "DATUM-UHRZEIT-" davor
 * (Zeitpunkt des jeweiligen Downloads, nicht der Erstellung – automatisch
 * erzeugte Dokumente existieren ja nicht dauerhaft, siehe
 * DocumentManager::virtualDocuments()).
 */
class DocumentNaming
{
    /** Anzeigename im Dokumentenmanager (ohne Dateiendung). */
    public static function displayName(string $docName): string
    {
        return $docName;
    }

    /** Dateisicherer Dateiname beim Herunterladen, inkl. Zeitstempel und Endung. */
    public static function downloadFilename(string $docName, string $extension): string
    {
        return self::withDownloadTimestamp(
            (string) str(self::displayName($docName))->slug() . '.' . $extension
        );
    }

    /** Stellt einem bereits fertigen Dateinamen (z.B. eines Uploads) den Download-Zeitstempel voran. */
    public static function withDownloadTimestamp(string $filename): string
    {
        return now()->format('Y-m-d_H-i-s') . '-' . $filename;
    }
}
