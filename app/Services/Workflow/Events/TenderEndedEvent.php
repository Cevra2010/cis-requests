<?php

namespace App\Services\Workflow\Events;

use App\Models\Project;
use Carbon\CarbonInterface;

class TenderEndedEvent implements WorkflowEventType
{
    public function key(): string
    {
        return 'tender.ended';
    }

    public function label(): string
    {
        return 'Ausschreibungszeitraum ist geendet';
    }

    public function icon(): string
    {
        return 'flag-checkered';
    }

    public function description(): string
    {
        return 'Tritt ein, sobald das Ausschreibungsende des Projekts erreicht ist.';
    }

    public function scheduled(): bool
    {
        return true;
    }

    public function occurredAt(Project $project): ?CarbonInterface
    {
        return $project->tender_ends_at;
    }
}
