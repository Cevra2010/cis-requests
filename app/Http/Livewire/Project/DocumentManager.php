<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Export\Models\ExportTemplate;
use Nwidart\Modules\Facades\Module;

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
            $document->folders = self::foldersFor($document);
        }

        $merged = $this->virtualDocuments()->concat($all);
        $inFolder = fn (string $folder) => fn (ProjectDocument $d) => in_array($folder, $d->folders, true);

        $counts = [
            'all'                => $merged->count(),
            self::FOLDER_TABLES  => $merged->filter($inFolder(self::FOLDER_TABLES))->count(),
            self::FOLDER_PDF     => $merged->filter($inFolder(self::FOLDER_PDF))->count(),
            self::FOLDER_UPLOADS => $merged->filter($inFolder(self::FOLDER_UPLOADS))->count(),
        ];

        $documents = $this->activeFolder === 'all'
            ? $merged
            : $merged->filter($inFolder($this->activeFolder))->values();

        return view('livewire.project.document-manager', compact('documents', 'counts'));
    }

    /**
     * Ein Dokument kann in mehreren Verzeichnissen gleichzeitig auftauchen:
     * nach Dateiformat (Tabellen/PDF-Dateien) UND – zusätzlich, nicht
     * ausschließend – unter "Uploads", wenn es tatsächlich hochgeladen wurde
     * (im Gegensatz zu den automatisch erzeugten virtuellen Dokumenten).
     *
     * @return array<int, string>
     */
    private static function foldersFor(ProjectDocument $document): array
    {
        $ext     = $document->extension();
        $folders = [];

        if (in_array($ext, self::TABLE_EXTENSIONS, true)) {
            $folders[] = self::FOLDER_TABLES;
        } elseif ($ext === 'pdf') {
            $folders[] = self::FOLDER_PDF;
        }

        if ($document->exists) {
            $folders[] = self::FOLDER_UPLOADS;
        }

        return $folders;
    }

    /**
     * "Dokumente", die es nie wirklich gibt: sehen im Dokumentenmanager wie
     * eine Datei aus, werden aber erst beim Herunterladen live erzeugt
     * (bestehende Export-Routen, keine eigene Erzeugungslogik hier). Als
     * nie gespeicherte ProjectDocument-Instanzen ($document->exists === false)
     * dargestellt, damit die Blade-View dieselben Anzeige-Helfer nutzen kann.
     */
    private function virtualDocuments(): \Illuminate\Support\Collection
    {
        $project = Project::where('cis_row_id', $this->projectId)->first();
        if (! $project) {
            return collect();
        }

        $items = collect([
            $this->virtualDocument(
                str($project->name)->slug() . '-ausschreibung.pdf',
                route('project.export.pdf', $project->cis_row_id)
            ),
            $this->virtualDocument(
                str($project->name)->slug() . '-uebersicht.pdf',
                route('project.overview.pdf', $project->cis_row_id)
            ),
        ]);

        if (Module::find('Export')?->isEnabled()) {
            $templates = ExportTemplate::with('columns')->orderBy('name')->get()
                ->filter(fn (ExportTemplate $t) => $t->columns->isNotEmpty());

            foreach ($templates as $template) {
                foreach (['xlsx' => 'xlsx', 'csv' => 'csv'] as $format => $ext) {
                    $items->push($this->virtualDocument(
                        "{$template->name}.{$ext}",
                        route('export.tender.table', [$project->cis_row_id, $template->cis_row_id, $format])
                    ));
                }
            }
        }

        return $items;
    }

    private function virtualDocument(string $name, string $url): ProjectDocument
    {
        $document = new ProjectDocument([
            'name'      => $name,
            'file_path' => $name,
        ]);
        $document->folders     = self::foldersFor($document);
        $document->downloadUrl = $url;

        return $document;
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
