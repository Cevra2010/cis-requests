<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\PositionAward;
use App\Models\Project;
use App\Models\ProjectProduct;
use Illuminate\Support\Collection;

/**
 * Ermittelt Bestpreis-Vorschläge je Position und Mindestwert-Konflikte je Angebot.
 * Trifft nie autonom Entscheidungen über bereits manuell zugewiesene Positionen
 * oder Angebote, deren Mindestwert-Unterschreitung der Nutzer bewusst akzeptiert hat.
 */
class AwardCalculator
{
    /** Günstigstes valides Angebot (aktiv, korrekt angeboten, Preis gesetzt) für eine Position. */
    public static function cheapestValidItem(ProjectProduct $position, ?string $excludeOfferId = null): ?OfferItem
    {
        return $position->offerItems()
            ->with('offer')
            ->where('not_offered', false)
            ->whereNotNull('price')
            ->whereHas('offer', function ($q) use ($excludeOfferId) {
                $q->where('active', true);
                if ($excludeOfferId) {
                    $q->where('cis_row_id', '!=', $excludeOfferId);
                }
            })
            ->get()
            ->sortBy('price')
            ->first();
    }

    /**
     * true, wenn unter den (nach Preis sortierten) validen Angeboten ein Gleichstand
     * beim günstigsten Preis besteht (Position ist damit nicht eindeutig zuordenbar).
     *
     * @param Collection<int, OfferItem> $sortedValidItems nach price aufsteigend sortiert
     */
    public static function isTiedAtCheapest(Collection $sortedValidItems): bool
    {
        if ($sortedValidItems->isEmpty()) {
            return false;
        }

        $minPrice = round((float) $sortedValidItems->first()->price, 2);

        return $sortedValidItems->filter(fn (OfferItem $i) => round((float) $i->price, 2) === $minPrice)->count() > 1;
    }

    /**
     * Setzt/aktualisiert die Zuordnung einer einzelnen Position auf das günstigste
     * valide Angebot – nur, wenn dieses eindeutig ist. Bei Preis-Gleichstand wird
     * bewusst kein Anbieter vorgeschlagen, damit der Nutzer manuell entscheidet.
     */
    public static function recomputePosition(ProjectProduct $position): void
    {
        $validItems = $position->offerItems()
            ->with('offer')
            ->where('not_offered', false)
            ->whereNotNull('price')
            ->whereHas('offer', fn ($q) => $q->where('active', true))
            ->get()
            ->sortBy('price');

        $best = static::isTiedAtCheapest($validItems) ? null : $validItems->first();

        PositionAward::updateOrCreate(
            ['cis_row_id_project_product' => $position->cis_row_id],
            [
                'cis_row_id_project'      => $position->cis_row_id_project,
                'cis_row_id_offer'        => $best?->cis_row_id_offer,
                'is_manual_override'      => false,
            ]
        );
    }

    /** Berechnet Vorschläge für alle Positionen eines Projekts, die nicht manuell überschrieben sind. */
    public static function recomputeAll(Project $project): void
    {
        foreach ($project->positions as $position) {
            $existing = $position->award;
            if ($existing && $existing->is_manual_override) {
                continue;
            }
            static::recomputePosition($position);
        }
    }

    /**
     * Wird aufgerufen, nachdem ein Angebot deaktiviert wurde: Positionen, die diesem
     * Angebot zugeordnet waren (ohne bewusstes manuelles Override genau auf dieses
     * Angebot), werden unter den verbleibenden aktiven Angeboten neu vorgeschlagen.
     */
    public static function reassignAfterDeactivation(Offer $offer): void
    {
        $awards = PositionAward::where('cis_row_id_offer', $offer->cis_row_id)->get();

        foreach ($awards as $award) {
            if ($award->is_manual_override) {
                // Bewusste manuelle Wahl auf ein nun inaktives Angebot bleibt sichtbar
                // stehen (UI kennzeichnet dies als Problem), wird aber nicht automatisch geändert.
                continue;
            }
            static::recomputePosition($award->position);
        }
    }

    /**
     * Liefert je Angebot mit aktuell zugeordneten Positionen die Summe und ob der
     * Mindestwert unterschritten ist (und nicht bewusst ignoriert wird).
     *
     * @return Collection<int, array{offer: Offer, total: float, min_value: float, conflict: bool}>
     */
    public static function conflicts(Project $project): Collection
    {
        $minValue = $project->effectiveMinOrderValue();

        return $project->offers()
            ->active()
            ->get()
            ->map(function (Offer $offer) use ($minValue) {
                $total    = $offer->total();
                $hasAward = PositionAward::where('cis_row_id_offer', $offer->cis_row_id)->exists();
                return [
                    'offer'     => $offer,
                    'total'     => $total,
                    'min_value' => $minValue,
                    'conflict'  => $hasAward && $minValue > 0 && $total < $minValue && ! $offer->min_value_ignored,
                ];
            })
            ->filter(fn ($row) => $row['conflict']);
    }
}
