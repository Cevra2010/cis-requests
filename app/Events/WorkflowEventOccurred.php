<?php

namespace App\Events;

use App\Models\Project;
use Carbon\CarbonInterface;

/**
 * Ein Workflow-Ereignis ist für ein Projekt eingetreten (siehe App\Services\Workflow\Events).
 * Der Workflow-Trigger-Listener reagiert darauf; andere Stellen des Systems können ebenfalls
 * per Event::listen() darauf reagieren.
 */
class WorkflowEventOccurred
{
    public function __construct(
        public readonly string $eventKey,
        public readonly Project $project,
        public readonly CarbonInterface $occurredAt,
    ) {
    }
}
