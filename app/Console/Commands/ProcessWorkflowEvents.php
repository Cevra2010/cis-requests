<?php

namespace App\Console\Commands;

use App\Services\Workflow\Events\WorkflowEvents;
use Illuminate\Console\Command;

class ProcessWorkflowEvents extends Command
{
    protected $signature = 'workflow:process-events';

    protected $description = 'Feuert fällige zeitgesteuerte Workflow-Ereignisse (z.B. Ausschreibungsbeginn/-ende) und führt deren Trigger-Maßnahmen aus.';

    public function handle(): int
    {
        $count = WorkflowEvents::processDue();
        $this->info("{$count} Projekt(e) geprüft.");

        return self::SUCCESS;
    }
}
