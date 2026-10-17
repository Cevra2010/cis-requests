<?php

namespace App\Services\Workflow;

use App\Models\Project;
use App\Models\ProjectStatusTransition;
use App\Models\Setting;

/**
 * Projektvariablen für Workflow-Maßnahmen-Texte: {{platzhalter}} wird durch
 * reinen Text ersetzt (KEIN Blade-Kompilieren von Admin-Text). Unbekannte
 * Platzhalter bleiben unverändert stehen.
 */
class ProjectVariables
{
    /** @return array<string, string> Platzhalter => Anzeigename, für die Editor-Chips. */
    public static function catalog(): array
    {
        return [
            'projekt_name'         => 'Projektname',
            'projekt_beschreibung' => 'Projektbeschreibung',
            'projekt_typ'          => 'Projekttyp',
            'projekt_link'         => 'Link zum Projekt',
            'status_neu'           => 'Neuer Status',
            'status_vorher'        => 'Vorheriger Status',
            'auftraggeber'         => 'Auftraggeber',
            'kategorie'            => 'Kategorie',
            'verantwortlich'       => 'Verantwortliche(r)',
            'ausschreibungsjahr'   => 'Ausschreibungsjahr',
            'faellig_am'           => 'Fälligkeitsdatum',
            'ausschreibung_beginn' => 'Ausschreibungsbeginn',
            'ausschreibung_ende'   => 'Ausschreibungsende',
            'mindestbestellwert'   => 'Mindestbestellwert',
            'kostenschaetzung'     => 'Kostenschätzung',
            'anzahl_positionen'    => 'Anzahl Positionen',
            'ausgeloest_von'       => 'Ausgelöst von',
            'datum'                => 'Heutiges Datum',
            'firma'                => 'Firma',
        ];
    }

    public static function render(string $template, Project $project, ?ProjectStatusTransition $transition = null): string
    {
        $values = self::values($project, $transition);

        return preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/',
            fn (array $m) => $values[$m[1]] ?? $m[0],
            $template
        );
    }

    /** @return array<string, string> */
    protected static function values(Project $project, ?ProjectStatusTransition $transition): array
    {
        $estimate = $project->costEstimate();

        return [
            'projekt_name'         => $project->name,
            'projekt_beschreibung' => (string) $project->description,
            'projekt_typ'          => $project->typeLabel(),
            'projekt_link'         => route('project.show', $project->cis_row_id),
            'status_neu'           => $transition?->to_label ?? $project->statusLabel(),
            'status_vorher'        => (string) $transition?->from_label,
            'auftraggeber'         => (string) $project->client,
            'kategorie'            => $project->categoryLabel(),
            'verantwortlich'       => $project->assigneeLabel(),
            'ausschreibungsjahr'   => (string) $project->tender_year,
            'faellig_am'           => $project->due_date?->format('d.m.Y') ?? '',
            'ausschreibung_beginn' => $project->tender_starts_at?->format('d.m.Y H:i') ?? '',
            'ausschreibung_ende'   => $project->tender_ends_at?->format('d.m.Y H:i') ?? '',
            'mindestbestellwert'   => number_format($project->effectiveMinOrderValue(), 2, ',', '.') . ' €',
            'kostenschaetzung'     => number_format((float) $estimate['total'], 2, ',', '.') . ' €',
            'anzahl_positionen'    => (string) $estimate['positions_count'],
            'ausgeloest_von'       => $transition ? $transition->actorLabel() : (auth()->user()?->name() ?? 'System'),
            'datum'                => now()->format('d.m.Y'),
            'firma'                => (string) Setting::get('company_name'),
        ];
    }
}
