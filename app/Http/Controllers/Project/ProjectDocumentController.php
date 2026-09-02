<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Storage;

class ProjectDocumentController extends Controller
{
    public function download(string $document)
    {
        $document = ProjectDocument::where('cis_row_id', $document)->firstOrFail();

        abort_unless($document->exists(), 404, 'Datei nicht mehr vorhanden.');

        return Storage::disk('local')->download($document->file_path, $document->name);
    }
}
