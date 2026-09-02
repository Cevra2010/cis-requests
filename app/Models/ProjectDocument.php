<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Dokumentenmanager: frei hochgeladene oder mit einem Arbeitsschritt
 * verknüpfte Dateien eines Projekts (z.B. die vom Händler zurückgesandte
 * Angebots-Excel-Datei). Dateien liegen im "local"-Storage (nicht öffentlich
 * erreichbar), Download läuft über eine authentifizierte Controller-Route.
 */
class ProjectDocument extends Model
{
    use CisUuid, SoftDeletes;

    public const LINK_OFFER_IMPORT = 'offer_import';

    protected $fillable = [
        'cis_row_id_project',
        'name',
        'file_path',
        'mime_type',
        'size',
        'linked_type',
        'linked_id',
        'cis_row_id_uploaded_by',
        'notes',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'cis_row_id_project', 'cis_row_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'cis_row_id_uploaded_by', 'cis_row_id');
    }

    public function sizeForHumans(): string
    {
        $bytes = (int) $this->size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }

    public function exists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }

    /**
     * Löscht die zugehörige Datei aus dem Storage. Muss vor einem harten
     * Löschen des Datensatzes explizit aufgerufen werden (kein Model-Event,
     * um versehentlichen Datenverlust bei Soft-Deletes auszuschließen).
     */
    public function deleteFile(): void
    {
        if ($this->exists()) {
            Storage::disk('local')->delete($this->file_path);
        }
    }
}
