<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Support\DocumentNaming;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Export\Models\ExportTemplate;
use Nwidart\Modules\Facades\Module;

class DocumentManager extends Component
{
    use WithFileUploads;

    /** Verzeichnisse orientieren sich am Projekt-Workflow statt am Dateityp. */
    public const FOLDER_GENERAL = 'general';
    public const FOLDER_TENDER  = 'tender';
    public const FOLDER_ORDER   = 'order';
    public const FOLDER_UPLOADS = 'uploads';

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
            $document->folders = self::foldersForUpload($document);
        }

        $merged = $this->virtualDocuments()->concat($all);
        $inFolder = fn (string $folder) => fn (ProjectDocument $d) => in_array($folder, $d->folders, true);

        $counts = [
            'all'                => $merged->count(),
            self::FOLDER_GENERAL => $merged->filter($inFolder(self::FOLDER_GENERAL))->count(),
            self::FOLDER_TENDER  => $merged->filter($inFolder(self::FOLDER_TENDER))->count(),
            self::FOLDER_ORDER   => $merged->filter($inFolder(self::FOLDER_ORDER))->count(),
            self::FOLDER_UPLOADS => $merged->filter($inFolder(self::FOLDER_UPLOADS))->count(),
        ];

        $documents = $this->activeFolder === 'all'
            ? $merged
            : $merged->filter($inFolder($this->activeFolder))->values();

        return view('livewire.project.document-manager', compact('documents', 'counts'));
    }

    /**
     * Ein hochgeladenes Dokument taucht immer zusätzlich unter "Uploads" auf
     * (im Gegensatz zu den automatisch erzeugten virtuellen Dokumenten) sowie
     * in genau einem Workflow-Verzeichnis: eine beim Angebotsvergleich
     * importierte Händler-Datei unter "Ausschreibung" (gehört inhaltlich zum
     * Angebotsprozess), alles andere unter "Allgemein" (unbekannter Zweck).
     *
     * @return array<int, string>
     */
    private static function foldersForUpload(ProjectDocument $document): array
    {
        $workflowFolder = $document->linked_type === ProjectDocument::LINK_OFFER_IMPORT
            ? self::FOLDER_TENDER
            : self::FOLDER_GENERAL;

        return [$workflowFolder, self::FOLDER_UPLOADS];
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
            $this->virtualDocument($project, 'Ausschreibung', 'pdf', route('project.export.pdf', $project->cis_row_id), self::FOLDER_TENDER),
            $this->virtualDocument($project, 'Projektübersicht', 'pdf', route('project.overview.pdf', $project->cis_row_id), self::FOLDER_GENERAL),
        ]);

        // Je genutzter fester, nicht-ausschreibungsrelevanter Quelle eine eigene
        // Materialanforderung (statt einer gemeinsamen PDF mit mehreren
        // Abschnitten) – bei z.B. zwei genutzten internen Quellen stehen damit
        // zwei getrennte Dokumente zur Verfügung, siehe Project::materialRequestGroups().
        foreach ($project->materialRequestGroups() as $group) {
            if (! $group['source']) {
                continue;
            }
            $items->push($this->virtualDocument(
                $project,
                'Materialanforderung - ' . $group['source']->name,
                'pdf',
                route('project.material-request.pdf', [$project->cis_row_id, $group['source']->cis_row_id]),
                self::FOLDER_ORDER
            ));
        }

        // Je Anbieter mit tatsächlich zugeordneten Positionen eine eigene Bestellliste,
        // sowohl als PDF als auch als CSV-/Excel-Tabelle (gleicher Inhalt).
        foreach ($project->offersWithAwards() as $offer) {
            $docName = 'Bestellliste - ' . $offer->source->name;

            $items->push($this->virtualDocument(
                $project, $docName, 'pdf',
                route('offer.orderlist.pdf', [$project->cis_row_id, $offer->cis_row_id]),
                self::FOLDER_ORDER
            ));
            foreach (['xlsx', 'csv'] as $format) {
                $items->push($this->virtualDocument(
                    $project, $docName, $format,
                    route('offer.orderlist.table', [$project->cis_row_id, $offer->cis_row_id, $format]),
                    self::FOLDER_ORDER
                ));
            }
        }

        if (Module::find('Export')?->isEnabled()) {
            $templates = ExportTemplate::with('columns')->orderBy('name')->get()
                ->filter(fn (ExportTemplate $t) => $t->columns->isNotEmpty());

            foreach ($templates as $template) {
                // Vorlagen "vor der Ausschreibung" gehören zu "Ausschreibung", Vorlagen
                // "nach der Ausschreibung/Auswertung" zu "Bestellung" (siehe ExportTemplate::PHASES).
                $folder = $template->phase === 'post_tender' ? self::FOLDER_ORDER : self::FOLDER_TENDER;

                foreach (['xlsx', 'csv'] as $format) {
                    $items->push($this->virtualDocument(
                        $project,
                        $template->name,
                        $format,
                        route('export.tender.table', [$project->cis_row_id, $template->cis_row_id, $format]),
                        $folder
                    ));
                }
            }
        }

        return $items;
    }

    private function virtualDocument(Project $project, string $docName, string $extension, string $url, string $folder): ProjectDocument
    {
        $name = DocumentNaming::displayName($project, $docName) . '.' . $extension;

        $document = new ProjectDocument([
            'name'      => $name,
            'file_path' => $name,
        ]);
        $document->folders     = [$folder];
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
