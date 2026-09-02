<?php

namespace App\Http\Livewire\Project;

use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentManager extends Component
{
    use WithFileUploads;

    public string $projectId;

    public $newFile = null;

    public string $newName = '';

    public string $newNotes = '';

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function render()
    {
        $documents = ProjectDocument::where('cis_row_id_project', $this->projectId)
            ->with('uploadedBy')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.project.document-manager', compact('documents'));
    }

    public function upload(): void
    {
        $this->validate([
            'newFile' => 'required|file|max:20480',
        ], [
            'newFile.required' => 'Bitte wähle eine Datei aus.',
            'newFile.max'      => 'Die Datei darf maximal 20 MB groß sein.',
        ]);

        self::storeUpload($this->newFile, $this->newName ?: null, $this->newNotes ?: null, $this->projectId);

        $this->newFile  = null;
        $this->newName  = '';
        $this->newNotes = '';
    }

    /**
     * Speichert einen Upload dauerhaft im Storage und legt den zugehörigen
     * ProjectDocument-Datensatz an. Öffentlich statisch nutzbar, damit andere
     * Komponenten (z.B. OfferComparison beim Angebotsimport) denselben Ablage-
     * Mechanismus verwenden, ohne den Dokumentenmanager selbst zu mounten.
     */
    public static function storeUpload(
        $uploadedFile,
        ?string $displayName,
        ?string $notes,
        string $projectId,
        ?string $linkedType = null,
        ?string $linkedId = null
    ): ProjectDocument {
        $extension = $uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension();
        $filename  = str()->uuid() . ($extension ? '.' . $extension : '');
        $path      = "project-documents/{$projectId}/{$filename}";

        Storage::disk('local')->put($path, file_get_contents($uploadedFile->getRealPath()));

        return ProjectDocument::create([
            'cis_row_id_project'      => $projectId,
            'name'                    => $displayName ?: $uploadedFile->getClientOriginalName(),
            'file_path'               => $path,
            'mime_type'               => $uploadedFile->getMimeType(),
            'size'                    => $uploadedFile->getSize(),
            'linked_type'             => $linkedType,
            'linked_id'               => $linkedId,
            'cis_row_id_uploaded_by'  => auth()->user()?->cis_row_id,
            'notes'                   => $notes,
        ]);
    }

    public function delete(string $documentId): void
    {
        $document = ProjectDocument::where('cis_row_id', $documentId)
            ->where('cis_row_id_project', $this->projectId)
            ->first();

        if (! $document) {
            return;
        }

        $document->deleteFile();
        $document->forceDelete();
    }
}
