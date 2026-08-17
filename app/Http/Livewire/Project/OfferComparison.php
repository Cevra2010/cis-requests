<?php

namespace App\Http\Livewire\Project;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;

class OfferComparison extends Component
{
    public string $projectId;

    public bool   $showCreateModal  = false;
    public string $newSourceId      = '';
    public string $newReference     = '';
    public ?string $newSubmittedAt  = null;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    #[On('positions-imported')]
    public function refresh(): void
    {
        // Neue Positionen bekommen beim nächsten Render automatisch leere OfferItems (siehe render()).
    }

    public function render()
    {
        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();

        $positions = $project->positions()->with('product')->get();
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

        $availableSources = ProductSource::whereNotIn('cis_row_id', $offers->pluck('cis_row_id_source'))
            ->orderBy('name')
            ->get();

        return view('livewire.project.offer-comparison', compact(
            'project', 'positions', 'offers', 'matrix', 'cheapestPerPosition', 'availableSources'
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

        $item?->update(['not_offered' => ! $item->not_offered]);
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
}
