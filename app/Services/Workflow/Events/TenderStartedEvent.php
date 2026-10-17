<?php

namespace App\Services\Workflow\Events;

use App\Models\Project;
use Carbon\CarbonInterface;

class TenderStartedEvent implements WorkflowEventType
{
    public function key(): string
    {
        return 'tender.started';
    }

    public function label(): string
    {
        return 'Ausschreibungszeitraum hat begonnen';
    }

    public function icon(): string
    {
        return 'play';
    }

    public function description(): string
    {
        return 'Tritt ein, sobald der Ausschreibungsbeginn des Projekts erreicht ist.';
    }

    public function scheduled(): bool
    {
        return true;
    }

    public function occurredAt(Project $project): ?CarbonInterface
    {
        return $project->tender_starts_at;
    }
}
