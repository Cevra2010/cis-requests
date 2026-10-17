<?php

namespace App\Services\Workflow\Events;

use App\Models\Project;
use Carbon\CarbonInterface;

/**
 * Eine Ereignisart, auf die ein Workflow-Trigger reagieren kann (z.B. "Ausschreibungszeitraum
 * ist geendet"). Künftige Module registrieren eigene Arten in ihrem ServiceProvider
 * (siehe WorkflowEventTypes::register()).
 *
 * Es gibt zwei Arten von Ereignissen:
 *  - zeitgesteuerte (scheduled() = true): der Zeitpunkt ergibt sich aus Projektdaten
 *    (occurredAt()); der Event-Manager prüft sie regelmäßig (siehe WorkflowEvents::processDue()).
 *  - ausgelöste (scheduled() = false): Code ruft WorkflowEvents::dispatch() an der passenden
 *    Stelle selbst auf.
 */
interface WorkflowEventType
{
    public function key(): string;

    public function label(): string;

    public function icon(): string;

    public function description(): string;

    public function scheduled(): bool;

    /** Zeitpunkt, zu dem das Ereignis für dieses Projekt eintritt; null = (noch) nicht festgelegt. Nur für scheduled() relevant. */
    public function occurredAt(Project $project): ?CarbonInterface;
}
