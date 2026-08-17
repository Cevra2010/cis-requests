<?php

namespace App\Http\Livewire\Project;

use App\Models\Offer;
use App\Models\PositionAward;
use App\Models\Project;
use App\Services\AwardCalculator;
use Livewire\Attributes\On;
use Livewire\Component;

class AwardManager extends Component
{
    public string $projectId;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    #[On('positions-imported')]
    public function refresh(): void
    {
        //
    }

    public function render()
    {
        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();

        $positions = $project->positions()->with(['product', 'award.offer.source', 'offerItems.offer.source'])->get();
        $offers    = $project->offers()->with('source')->orderBy('created_at')->get();
        $conflicts = AwardCalculator::conflicts($project);

        // Summe je Angebot (für die Übersichtskarten unten)
        $summaries = $offers->map(function (Offer $offer) {
            return [
                'offer' => $offer,
                'total' => $offer->total(),
                'count' => PositionAward::where('cis_row_id_offer', $offer->cis_row_id)->count(),
            ];
        })->filter(fn ($s) => $s['count'] > 0);

        return view('livewire.project.award-manager', compact(
            'project', 'positions', 'offers', 'conflicts', 'summaries'
        ));
    }

    public function computeSuggestions(): void
    {
        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();
        AwardCalculator::recomputeAll($project);
    }

    public function assignManual(string $positionId, string $offerId): void
    {
        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();

        PositionAward::updateOrCreate(
            ['cis_row_id_project_product' => $positionId],
            [
                'cis_row_id_project' => $project->cis_row_id,
                'cis_row_id_offer'   => $offerId,
                'is_manual_override' => true,
            ]
        );
    }

    public function resetToSuggestion(string $positionId): void
    {
        $award = PositionAward::where('cis_row_id_project_product', $positionId)->first();
        $position = \App\Models\ProjectProduct::find($positionId);

        if ($award) {
            $award->update(['is_manual_override' => false]);
        }
        if ($position) {
            AwardCalculator::recomputePosition($position);
        }
    }

    public function excludeOffer(string $offerId): void
    {
        $offer = Offer::findOrFail($offerId);
        $offer->update(['active' => false]);
        AwardCalculator::reassignAfterDeactivation($offer);
    }

    public function reactivateOffer(string $offerId): void
    {
        Offer::where('cis_row_id', $offerId)->update(['active' => true]);
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
