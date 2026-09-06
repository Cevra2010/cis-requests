<?php

namespace App\Http\Livewire\Project;

use App\Models\Offer;
use App\Models\OfferChildItem;
use App\Models\OfferItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use App\Models\ProjectDocument;
use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Nwidart\Modules\Facades\Module;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OfferComparison extends Component
{
    use WithFileUploads;

    public string $projectId;

    public bool   $showCreateModal  = false;
    public string $newSourceId      = '';
    public string $newReference     = '';
    public ?string $newSubmittedAt  = null;

    /** 'overview' = Gesamtübersicht, 'sequential' = Angebote nacheinander bearbeiten */
    public string $viewMode = 'overview';

    public ?string $currentOfferId = null;

    // ── Angebot importieren (Export-Import-Modul) ───────────────────────────
    public $importFile = null;
    public string $importTemplateId = '';
    public ?string $importResult = null;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    // ── Einzeln bearbeiten ───────────────────────────────────────────────────

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['overview', 'sequential'], true) ? $mode : 'overview';
    }

    public function selectOffer(string $offerId): void
    {
        $this->currentOfferId = $offerId;
        $this->viewMode = 'sequential';
        $this->importResult = null;
    }

    public function nextOffer(): void
    {
        $this->stepOffer(1);
    }

    public function previousOffer(): void
    {
        $this->stepOffer(-1);
    }

    private function stepOffer(int $direction): void
    {
        $this->importResult = null;
        $offerIds = Offer::where('cis_row_id_project', $this->projectId)->orderBy('created_at')->pluck('cis_row_id');
        if ($offerIds->isEmpty()) {
            return;
        }

        $index = $this->currentOfferId ? $offerIds->search($this->currentOfferId) : false;
        $index = $index === false ? 0 : $index;
        $newIndex = max(0, min($offerIds->count() - 1, $index + $direction));

        $this->currentOfferId = $offerIds[$newIndex];
    }

    #[On('positions-imported')]
    public function refresh(): void
    {
        // Neue Positionen bekommen beim nächsten Render automatisch leere OfferItems (siehe render()).
    }

    public function render()
    {
        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();

        // Setprodukte erscheinen nie als eigene Position (siehe Product::isSet()) –
        // nur ihre Mitgliedsprodukte, die über aggregatedChildPositions() unten
        // ohnehin projektweit erfasst werden. Nicht-ausschreibungsrelevante
        // Positionen (feste, interne Quelle, siehe Product::isTenderRelevant())
        // werden gar nicht erst ausgeschrieben und tauchen daher im
        // Angebotsvergleich nicht auf.
        $positions = $project->positions()->with(['product.childs', 'product.source', 'award.offer.source'])->get()
            ->reject(fn ($p) => ! $p->product || $p->product->isSet() || ! $p->product->isTenderRelevant())
            ->values();
        $offers    = $project->offers()->with('source')->orderBy('created_at')->get();

        // Für neu importierte Positionen fehlende OfferItems je bestehendem Angebot nachziehen.
        foreach ($offers as $offer) {
            $existingPositionIds = $offer->items()->pluck('cis_row_id_project_product')->toArray();
            foreach ($positions as $position) {
                if (! in_array($position->cis_row_id, $existingPositionIds, true)) {
                    OfferItem::create([
                        'cis_row_id_offer'           => $offer->cis_row_id,
                        'cis_row_id_project_product' => $position->cis_row_id,
                    ]);
                }
            }
        }

        // Unterprodukte bekommen eine eigene, über alle Positionen aggregierte
        // Vergleichszeile (z.B. "Neubauschlüssel" 2× statt einmal je Elternprodukt).
        $childPositions = $project->aggregatedChildPositions();

        foreach ($offers as $offer) {
            $existingChildProductIds = $offer->childItems()->pluck('cis_row_id_product')->toArray();
            foreach ($childPositions as $childPosition) {
                $childId = $childPosition['product']->cis_row_id;
                if (! in_array($childId, $existingChildProductIds, true)) {
                    OfferChildItem::create([
                        'cis_row_id_offer'   => $offer->cis_row_id,
                        'cis_row_id_product' => $childId,
                    ]);
                }
            }
        }

        // Matrix: [positionId => [offerId => OfferItem]]
        $matrix = [];
        foreach ($offers as $offer) {
            foreach ($offer->items as $item) {
                $matrix[$item->cis_row_id_project_product][$offer->cis_row_id] = $item;
            }
        }

        // Günstigstes valides Angebot je Position (für Hervorhebung)
        $cheapestPerPosition = [];
        foreach ($positions as $position) {
            $best = null;
            foreach ($matrix[$position->cis_row_id] ?? [] as $offerId => $item) {
                $offer = $offers->firstWhere('cis_row_id', $offerId);
                if (! $offer || ! $offer->active || $item->not_offered || $item->price === null) {
                    continue;
                }
                if ($best === null || (float) $item->price < (float) $best) {
                    $best = (float) $item->price;
                }
            }
            $cheapestPerPosition[$position->cis_row_id] = $best;
        }

        // Matrix für Unterprodukte: [childProductId => [offerId => OfferChildItem]]
        $childMatrix = [];
        foreach ($offers as $offer) {
            foreach ($offer->childItems as $item) {
                $childMatrix[$item->cis_row_id_product][$offer->cis_row_id] = $item;
            }
        }

        $cheapestPerChildPosition = [];
        foreach ($childPositions as $childPosition) {
            $childId = $childPosition['product']->cis_row_id;
            $best    = null;
            foreach ($childMatrix[$childId] ?? [] as $offerId => $item) {
                $offer = $offers->firstWhere('cis_row_id', $offerId);
                if (! $offer || ! $offer->active || $item->not_offered || $item->price === null) {
                    continue;
                }
                if ($best === null || (float) $item->price < (float) $best) {
                    $best = (float) $item->price;
                }
            }
            $cheapestPerChildPosition[$childId] = $best;
        }

        // Fortschritt "geprüft": zählt je Position (Haupt- und Unterprodukt) mit
        // mindestens einem validen Angebot, ob das jeweils günstigste davon bereits
        // als geprüft markiert wurde (oder die Position keinen weiteren Anbieter
        // braucht, weil sie korrekt als "nicht korrekt angeboten" gekennzeichnet ist,
        // ist hier bewusst NICHT als "geprüft" gezählt – das Häkchen bestätigt aktiv
        // die Korrektheit des günstigsten Preises).
        $reviewTotal   = 0;
        $reviewChecked = 0;
        foreach ($positions as $position) {
            $cheapest = $cheapestPerPosition[$position->cis_row_id] ?? null;
            if ($cheapest === null) {
                continue;
            }
            $reviewTotal++;
            foreach ($matrix[$position->cis_row_id] ?? [] as $item) {
                if (! $item->not_offered && $item->price !== null
                    && (float) $item->price === (float) $cheapest && $item->isChecked()) {
                    $reviewChecked++;
                    break;
                }
            }
        }
        foreach ($childPositions as $childPosition) {
            $childId  = $childPosition['product']->cis_row_id;
            $cheapest = $cheapestPerChildPosition[$childId] ?? null;
            if ($cheapest === null) {
                continue;
            }
            $reviewTotal++;
            foreach ($childMatrix[$childId] ?? [] as $item) {
                if (! $item->not_offered && $item->price !== null
                    && (float) $item->price === (float) $cheapest && $item->isChecked()) {
                    $reviewChecked++;
                    break;
                }
            }
        }
        $reviewProgress = ['checked' => $reviewChecked, 'total' => $reviewTotal];

        $availableSources = ProductSource::whereNotIn('cis_row_id', $offers->pluck('cis_row_id_source'))
            ->orderBy('name')
            ->get();

        // Positionen, die abweichend vom günstigsten Preis bestellt wurden (z.B. manuell
        // unter "Bestellung" einem anderen Anbieter zugeordnet) – für Hinweis in der Übersicht.
        $deviations = [];
        foreach ($positions as $position) {
            $award = $position->award;
            if (! $award || ! $award->cis_row_id_offer) {
                continue;
            }

            $awardedItem  = $matrix[$position->cis_row_id][$award->cis_row_id_offer] ?? null;
            $awardedPrice = ($awardedItem && ! $awardedItem->not_offered) ? $awardedItem->price : null;
            $cheapest     = $cheapestPerPosition[$position->cis_row_id] ?? null;

            if ($awardedPrice !== null && $cheapest !== null && (float) $awardedPrice !== (float) $cheapest) {
                $deviations[$position->cis_row_id] = [
                    'source_name'    => $award->offer?->source?->name ?? '–',
                    'awarded_price'  => (float) $awardedPrice,
                    'cheapest_price' => (float) $cheapest,
                ];
            }
        }

        // ── Für "Einzeln bearbeiten" ─────────────────────────────────────────────
        $currentOffer      = null;
        $currentOfferIndex = null;
        if ($offers->isNotEmpty()) {
            if (! $this->currentOfferId || ! $offers->contains('cis_row_id', $this->currentOfferId)) {
                $this->currentOfferId = $offers->first()->cis_row_id;
            }
            $currentOffer      = $offers->firstWhere('cis_row_id', $this->currentOfferId);
            $currentOfferIndex = $offers->search(fn (Offer $o) => $o->cis_row_id === $this->currentOfferId);
        }

        // Angebot importieren: nur Vorlagen mit Händler-Preisfeld sind geeignet.
        $importableTemplates = Module::find('Export')?->isEnabled()
            ? \Modules\Export\Models\ExportTemplate::with('columns')->orderBy('name')->get()
                ->filter(fn ($t) => $t->hasVendorPriceColumn())->values()
            : collect();

        $currentOfferImportDocument = $currentOffer
            ? ProjectDocument::where('cis_row_id_project', $this->projectId)
                ->where('linked_type', ProjectDocument::LINK_OFFER_IMPORT)
                ->where('linked_id', $currentOffer->cis_row_id)
                ->latest()
                ->first()
            : null;

        return view('livewire.project.offer-comparison', compact(
            'project', 'positions', 'offers', 'matrix', 'cheapestPerPosition', 'availableSources',
            'deviations', 'currentOffer', 'currentOfferIndex',
            'childPositions', 'childMatrix', 'cheapestPerChildPosition', 'reviewProgress',
            'importableTemplates', 'currentOfferImportDocument'
        ));
    }

    // ── Angebot anlegen ──────────────────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->newSourceId     = '';
        $this->newReference    = '';
        $this->newSubmittedAt  = null;
    }

    public function createOffer(): void
    {
        $this->validate([
            'newSourceId' => 'required|string|exists:product_sources,cis_row_id',
        ]);

        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();

        $exists = Offer::where('cis_row_id_project', $project->cis_row_id)
            ->where('cis_row_id_source', $this->newSourceId)
            ->exists();

        if ($exists) {
            return;
        }

        $offer = Offer::create([
            'cis_row_id_project' => $project->cis_row_id,
            'cis_row_id_source'  => $this->newSourceId,
            'reference'          => $this->newReference ?: null,
            'submitted_at'       => $this->newSubmittedAt ?: null,
        ]);

        foreach ($project->positions as $position) {
            OfferItem::create([
                'cis_row_id_offer'           => $offer->cis_row_id,
                'cis_row_id_project_product' => $position->cis_row_id,
            ]);
        }

        $this->showCreateModal = false;
    }

    // ── Preise / Positionsstatus ─────────────────────────────────────────────

    public function saveItemPrice(string $offerId, string $positionId, $value): void
    {
        $value = trim((string) $value);
        $price = $value === '' ? null : (float) str_replace(',', '.', $value);

        $item = OfferItem::where('cis_row_id_offer', $offerId)
            ->where('cis_row_id_project_product', $positionId)
            ->first();

        if (! $item) {
            return;
        }

        $item->update(['price' => $price]);

        if ($price !== null) {
            $offer    = Offer::find($offerId);
            $position = $item->position;
            $product  = $position?->product;

            if ($offer && $product) {
                Price::add($price, $product, $offer->source);
            }
        }
    }

    public function toggleNotOffered(string $offerId, string $positionId): void
    {
        $item = OfferItem::where('cis_row_id_offer', $offerId)
            ->where('cis_row_id_project_product', $positionId)
            ->first();

        if (! $item) {
            return;
        }

        $notOffered = ! $item->not_offered;
        // Geprüft/nicht korrekt angeboten schließen sich gegenseitig aus.
        $item->update(['not_offered' => $notOffered, 'checked_at' => $notOffered ? null : $item->checked_at]);
    }

    /**
     * Markiert das jeweils günstigste (valide) Angebot einer Position als geprüft.
     * Nur für dieses eine Item relevant – siehe Blade: der Button erscheint nur
     * beim aktuell günstigsten Angebot einer Zeile.
     */
    public function toggleChecked(string $offerId, string $positionId): void
    {
        $item = OfferItem::where('cis_row_id_offer', $offerId)
            ->where('cis_row_id_project_product', $positionId)
            ->first();

        $item?->update(['checked_at' => $item->isChecked() ? null : now()]);
    }

    public function saveChildItemPrice(string $offerId, string $productId, $value): void
    {
        $value = trim((string) $value);
        $price = $value === '' ? null : (float) str_replace(',', '.', $value);

        $item = OfferChildItem::where('cis_row_id_offer', $offerId)
            ->where('cis_row_id_product', $productId)
            ->first();

        if (! $item) {
            return;
        }

        $item->update(['price' => $price]);

        if ($price !== null) {
            $offer   = Offer::find($offerId);
            $product = Product::where('cis_row_id', $productId)->first();

            if ($offer && $product) {
                Price::add($price, $product, $offer->source);
            }
        }
    }

    public function toggleChildNotOffered(string $offerId, string $productId): void
    {
        $item = OfferChildItem::where('cis_row_id_offer', $offerId)
            ->where('cis_row_id_product', $productId)
            ->first();

        if (! $item) {
            return;
        }

        $notOffered = ! $item->not_offered;
        $item->update(['not_offered' => $notOffered, 'checked_at' => $notOffered ? null : $item->checked_at]);
    }

    public function toggleChildChecked(string $offerId, string $productId): void
    {
        $item = OfferChildItem::where('cis_row_id_offer', $offerId)
            ->where('cis_row_id_product', $productId)
            ->first();

        $item?->update(['checked_at' => $item->isChecked() ? null : now()]);
    }

    public function toggleActive(string $offerId): void
    {
        $offer = Offer::findOrFail($offerId);
        $offer->update(['active' => ! $offer->active]);

        if (! $offer->active) {
            \App\Services\AwardCalculator::reassignAfterDeactivation($offer);
        }
    }

    public function ignoreMinValue(string $offerId): void
    {
        Offer::where('cis_row_id', $offerId)->update(['min_value_ignored' => true]);
    }

    public function respectMinValue(string $offerId): void
    {
        Offer::where('cis_row_id', $offerId)->update(['min_value_ignored' => false]);
    }

    // ── Angebot importieren (Export-Import-Modul) ───────────────────────────

    /**
     * Liest die vom Händler ausgefüllte Excel-/CSV-Liste ein (siehe
     * TenderExporter::IMPORT_KEY_HEADER) und übernimmt die eingetragenen
     * Preise für das aktuell in "Einzeln bearbeiten" gewählte Angebot – über
     * dieselben saveItemPrice()/saveChildItemPrice()-Methoden wie bei manueller
     * Eingabe, damit Price::add() etc. konsistent mitläuft. Die Datei selbst
     * wird als ProjectDocument (verknüpft mit diesem Angebot) im Storage
     * abgelegt; eine zuvor für dieses Angebot importierte Datei wird ersetzt.
     */
    public function importOfferFile(): void
    {
        $this->validate([
            'importFile'       => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'importTemplateId' => 'required|string',
        ], [
            'importFile.required' => 'Bitte wähle eine Datei aus.',
            'importFile.mimes'    => 'Nur .xlsx-, .xls- oder .csv-Dateien sind erlaubt.',
            'importFile.max'      => 'Die Datei darf maximal 10 MB groß sein.',
        ]);

        if (! $this->currentOfferId) {
            $this->addError('importFile', 'Bitte zuerst ein Angebot auswählen.');
            return;
        }

        $template = \Modules\Export\Models\ExportTemplate::with('columns')->find($this->importTemplateId);
        if (! $template || ! $template->hasVendorPriceColumn()) {
            $this->addError('importTemplateId', 'Bitte eine Vorlage mit Händler-Preisfeld wählen.');
            return;
        }

        $columns          = $template->columns->values();
        $unitPriceIndex   = $columns->search(fn ($c) => $c->field_key === 'vendor_unit_price');
        $totalPriceIndex  = $columns->search(fn ($c) => $c->field_key === 'vendor_total_price');
        $importKeyIndex   = $columns->count();

        try {
            $spreadsheet = IOFactory::load($this->importFile->getRealPath());
        } catch (\Throwable $e) {
            $this->addError('importFile', 'Die Datei konnte nicht gelesen werden: ' . $e->getMessage());
            return;
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        array_shift($rows); // Kopfzeile überspringen

        $project        = Project::where('cis_row_id', $this->projectId)->firstOrFail();
        $childQuantities = $project->aggregatedChildPositions()
            ->mapWithKeys(fn ($c) => [$c['product']->cis_row_id => $c['quantity']]);

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $key = trim((string) ($row[$importKeyIndex] ?? ''));
            if ($key === '' || ! str_contains($key, ':')) {
                continue;
            }
            [$type, $id] = explode(':', $key, 2);

            $unitPriceRaw  = $unitPriceIndex !== false ? trim((string) ($row[$unitPriceIndex] ?? '')) : '';
            $totalPriceRaw = $totalPriceIndex !== false ? trim((string) ($row[$totalPriceIndex] ?? '')) : '';

            $unitPrice = null;
            if ($unitPriceRaw !== '' && is_numeric(str_replace(',', '.', $unitPriceRaw))) {
                $unitPrice = str_replace(',', '.', $unitPriceRaw);
            } elseif ($totalPriceRaw !== '' && is_numeric(str_replace(',', '.', $totalPriceRaw))) {
                $totalPrice = (float) str_replace(',', '.', $totalPriceRaw);
                $quantity   = $type === 'C'
                    ? (int) ($childQuantities[$id] ?? 0)
                    : (int) (\App\Models\ProjectProduct::where('cis_row_id', $id)->value('product_count') ?? 0);
                $unitPrice  = $quantity > 0 ? (string) round($totalPrice / $quantity, 2) : null;
            }

            if ($unitPrice === null) {
                $skipped++;
                continue;
            }

            if ($type === 'C') {
                $this->saveChildItemPrice($this->currentOfferId, $id, $unitPrice);
            } elseif ($type === 'P') {
                $this->saveItemPrice($this->currentOfferId, $id, $unitPrice);
            } else {
                $skipped++;
                continue;
            }
            $updated++;
        }

        // Zuvor für dieses Angebot importierte Datei ersetzen (eine aktive Datei je Angebot).
        ProjectDocument::where('cis_row_id_project', $this->projectId)
            ->where('linked_type', ProjectDocument::LINK_OFFER_IMPORT)
            ->where('linked_id', $this->currentOfferId)
            ->get()
            ->each(function (ProjectDocument $doc) {
                $doc->deleteFile();
                $doc->forceDelete();
            });

        $offerName = Offer::with('source')->find($this->currentOfferId)?->source?->name ?? 'Angebot';
        DocumentManager::storeUpload(
            $this->importFile,
            $this->importFile->getClientOriginalName(),
            "Preisimport für {$offerName} ({$updated} übernommen, {$skipped} übersprungen)",
            $this->projectId,
            ProjectDocument::LINK_OFFER_IMPORT,
            $this->currentOfferId
        );

        $this->importFile       = null;
        $this->importTemplateId = '';
        $this->importResult     = "{$updated} Preise übernommen, {$skipped} Zeile(n) übersprungen.";
    }
}
