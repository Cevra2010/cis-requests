<?php

namespace Modules\Export\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Services\Datasets\ProductDataset;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Import-/Export-Werkzeug für Systemdaten: listet alle Vorlagen, die für
 * "Import-/Export-Werkzeug" freigegeben sind. Ein Import läuft immer in zwei
 * Schritten – erst eine Prüfung (Vorschau ohne Schreiben), dann "Übernehmen";
 * dabei wird die Datei erneut geprüft, nie ein vom Browser geliefertes Ergebnis geschrieben.
 */
class DataTransfer extends Component
{
    use WithFileUploads;

    public ?string $importTemplateId = null;

    public $importFile = null;

    public bool $allowCreate = false;

    /** @var array<int, array<string, mixed>>|null */
    public ?array $plan = null;

    public ?string $resultMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('export.transfer'), 403);
    }

    public function openImport(string $templateId): void
    {
        $this->resetImport();
        $this->importTemplateId = $templateId;
    }

    public function closeImport(): void
    {
        $this->resetImport();
    }

    private function resetImport(): void
    {
        $this->importTemplateId = null;
        $this->importFile       = null;
        $this->allowCreate      = false;
        $this->plan             = null;
        $this->resultMessage    = null;
        $this->resetErrorBag();
    }

    public function updatedImportFile(): void
    {
        $this->plan          = null;
        $this->resultMessage = null;
    }

    public function updatedAllowCreate(): void
    {
        $this->plan = null;
    }

    public function check(): void
    {
        $this->plan          = null;
        $this->resultMessage = null;
        $this->plan          = $this->buildPlan();
    }

    public function apply(): void
    {
        abort_unless(auth()->user()?->hasPermission('export.transfer'), 403);

        $plan = $this->buildPlan();
        if ($plan === null) {
            return;
        }

        $result = app(ProductDataset::class)->apply($plan);

        $errors = collect($plan)->where('action', 'error')->count();
        $this->resultMessage = "{$result['updated']} aktualisiert, {$result['created']} neu angelegt"
            . ($errors ? ", {$errors} Zeile(n) mit Fehlern nicht übernommen." : '.');
        $this->plan          = null;
        $this->importFile    = null;
    }

    /** @return array<int, array<string, mixed>>|null null bei Fehler (am Formular gemeldet) */
    private function buildPlan(): ?array
    {
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240'], [
            'importFile.required' => 'Bitte wähle eine Datei aus.',
            'importFile.mimes'    => 'Nur .xlsx-, .xls- oder .csv-Dateien sind erlaubt.',
            'importFile.max'      => 'Die Datei darf maximal 10 MB groß sein.',
        ]);

        $template = ExportTemplate::with('columns')->find($this->importTemplateId);
        if (! $template || ! $template->hasUsage('data_transfer') || ! $template->include_import_key) {
            $this->addError('importFile', 'Diese Vorlage ist für den Import nicht freigegeben (Import-Key fehlt).');
            return null;
        }

        try {
            $extension = strtolower($this->importFile->getClientOriginalExtension());
            if (in_array($extension, ['csv', 'txt'], true)) {
                $reader = new Csv();
                $reader->setDelimiter(';');
                $spreadsheet = $reader->load($this->importFile->getRealPath());
            } else {
                $spreadsheet = IOFactory::load($this->importFile->getRealPath());
            }
        } catch (\Throwable $e) {
            $this->addError('importFile', 'Die Datei konnte nicht gelesen werden: ' . $e->getMessage());
            return null;
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        array_shift($rows); // Kopfzeile

        return match ($template->dataset) {
            ProductDataset::KEY => app(ProductDataset::class)->plan($template, $rows, $this->allowCreate),
            default             => null,
        };
    }

    public function render()
    {
        $templates = ExportTemplate::with('columns')->orderBy('name')->get()
            ->filter(fn (ExportTemplate $t) => $t->hasUsage('data_transfer') && $t->columns->isNotEmpty());

        return view('export::livewire.data-transfer', [
            'templatesByDataset' => $templates->groupBy('dataset'),
            'datasets'           => ExportTemplate::DATASETS,
            'importTemplate'     => $this->importTemplateId ? $templates->firstWhere('cis_row_id', $this->importTemplateId) : null,
        ]);
    }
}
