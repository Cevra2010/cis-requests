<?php

namespace App\Support;

use App\Models\Project;

/**
 * Einheitliche Benennung für alle im Dokumentenmanager sichtbaren Dokumente
 * (automatisch erzeugte wie hochgeladene): im Manager selbst immer
 * "PROJEKTNAME-DOKUMENTENNAME", beim tatsächlichen Herunterladen zusätzlich
 * mit "DATUM-UHRZEIT-" vorangestellt (Zeitpunkt des jeweiligen Downloads,
 * nicht der Erstellung – automatisch erzeugte Dokumente existieren ja nicht
 * dauerhaft, siehe DocumentManager::virtualDocuments()).
 */
class DocumentNaming
{
    /** Anzeigename im Dokumentenmanager (ohne Dateiendung). */
    public static function displayName(Project $project, string $docName): string
    {
        return "{$project->name}-{$docName}";
    }

    /** Dateisicherer Dateiname beim Herunterladen, inkl. Zeitstempel und Endung. */
    public static function downloadFilename(Project $project, string $docName, string $extension): string
    {
        return self::withDownloadTimestamp(
            (string) str(self::displayName($project, $docName))->slug() . '.' . $extension
        );
    }

    /** Stellt einem bereits fertigen Dateinamen (z.B. eines Uploads) den Download-Zeitstempel voran. */
    public static function withDownloadTimestamp(string $filename): string
    {
        return now()->format('Y-m-d_H-i-s') . '-' . $filename;
    }
}
