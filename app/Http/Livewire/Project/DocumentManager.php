<?php

namespace App\Http\Livewire\Project;

use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentManager extends Component
{
    use WithFileUploads;

    public const FOLDER_TABLES  = 'tables';
    public const FOLDER_PDF     = 'pdf';
    public const FOLDER_UPLOADS = 'uploads';

    private const TABLE_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public string $projectId;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newFiles = [];

    public string $newName = '';

    public string $newNotes = '';

    public string $activeFolder = 'all';

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function setFolder(string $folder): void
    {
        $this->activeFolder = $folder;
    }

    public function render()
    {
        $all = ProjectDocument::where('cis_row_id_project', $this->projectId)
            ->with('uploadedBy')
            ->orderByDesc('created_at')
            ->get();

        foreach ($all as $document) {
            $document->folder = self::folderFor($document);
        }

        $counts = [
            self::FOLDER_TABLES  => $all->where('folder', self::FOLDER_TABLES)->count(),
            self::FOLDER_PDF     => $all->where('folder', self::FOLDER_PDF)->count(),
            self::FOLDER_UPLOADS => $all->where('folder', self::FOLDER_UPLOADS)->count(),
        ];

        $documents = $this->activeFolder === 'all'
            ? $all
            : $all->where('folder', $this->activeFolder)->values();

        return view('livewire.project.document-manager', compact('documents', 'counts'));
    }

    private static function folderFor(ProjectDocument $document): string
    {
        $ext = $document->extension();

        return match (true) {
            in_array($ext, self::TABLE_EXTENSIONS, true) => self::FOLDER_TABLES,
            $ext === 'pdf' => self::FOLDER_PDF,
            default => self::FOLDER_UPLOADS,
        };
    }

    /**
     * Bewusst NICHT "upload()" genannt: Livewires eigenes $wire-JS-Objekt
     * definiert bereits eine interne Methode "upload" (für den Datei-Upload-
     * Mechanismus selbst) – ein PHP-Methodenname "upload" würde von
     * wire:submit mit dieser internen JS-Funktion kollidieren
     * ("Cannot read properties of undefined (reading 'name')").
     */
    public function submitUpload(): void
    {
        $this->validate([
            'newFiles'   => 'required|array|min:1',
            'newFiles.*' => 'file|max:20480',
        ], [
            'newFiles.required' => 'Bitte wähle mindestens eine Datei aus.',
            'newFiles.*.max'    => 'Jede Datei darf maximal 20 MB groß sein.',
        ]);

        // Ein eigener Anzeigename ergibt nur bei genau einer Datei Sinn –
        // bei mehreren behält jede Datei ihren Originalnamen.
        $useCustomName = count($this->newFiles) === 1 ? ($this->newName ?: null) : null;

        foreach ($this->newFiles as $file) {
            self::storeUpload($file, $useCustomName, $this->newNotes ?: null, $this->projectId);
        }

        $this->newFiles = [];
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
