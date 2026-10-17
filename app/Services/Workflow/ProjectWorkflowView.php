<?php

namespace App\Services\Workflow;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkflowStatus;
use App\Services\Workflow\Actions\WorkflowActionTypes;
use Illuminate\Support\Collection;

/**
 * Presenter für den Wizard in project/show.blade.php – bündelt alle Werte, die
 * die alte ~90-Zeilen-@php-Sektion dort einzeln berechnet hat.
 */
class ProjectWorkflowView
{
    public function __construct(
        public Project $project,
        public Collection $steps,
        public ?WorkflowStatus $current,
        public ?WorkflowStatus $next,
        public ?WorkflowStatus $previous,
        public bool $canAdvance,
        public bool $canRevert,
        public bool $nextCrossesLock,
        public bool $prevUnlocks,
        public array $advanceBlockedReasons,
        public array $revertBlockedReasons,
        /** @var array<int, array{when: \Carbon\CarbonInterface, event: string, actions: string[]}> */
        public array $scheduled,
        public Collection $history,
        public array $nextActions = [],
    ) {
    }

    public static function for(Project $project, ?User $user): self
    {
        $workflow = $project->workflow();
        $current  = $project->currentStep();
        $next     = $project->nextStep();
        $previous = $project->previousStep();

        $canAdvance      = $next !== null && $workflow->canApprove($next, $user);
        $nextCrossesLock = $next !== null
            && ! $workflow->isLocked($project->status_code)
            && $workflow->isLocked($next->code);

        $prevUnlocks = $previous !== null
            && $workflow->isLocked($project->status_code)
            && ! $workflow->isLocked($previous->code);

        $canRevert = $previous !== null
            && $workflow->canApprove($current, $user)
            && (! $prevUnlocks || $workflow->canOverrideLock($user));

        $advanceBlockedReasons = [];
        if ($next && ! $canAdvance) {
            $advanceBlockedReasons[] = 'Freigabe erforderlich durch: ' . implode(', ', $workflow->approverLabels($next)) . '.';
        }

        $revertBlockedReasons = [];
        if ($previous && ! $canRevert) {
            if (! $workflow->canApprove($current, $user)) {
                $revertBlockedReasons[] = 'Freigabe erforderlich durch: ' . implode(', ', $workflow->approverLabels($current)) . '.';
            }
            if ($prevUnlocks && ! $workflow->canOverrideLock($user)) {
                $revertBlockedReasons[] = 'Aufheben der Fixierung erfordert: ' . implode(', ', $workflow->lockOverrideLabels()) . '.';
            }
        }

        $nextActions = [];
        if ($next) {
            $nextActions = \App\Models\WorkflowAction::where('cis_row_id_workflow_status', $next->cis_row_id)
                ->where('trigger', 'enter')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($a) {
                    $type = WorkflowActionTypes::find($a->type);
                    return $type ? ['icon' => $type->icon(), 'summary' => $type->summary($a->config ?? [])] : null;
                })
                ->filter()
                ->values()
                ->all();
        }

        return new self(
            project: $project,
            steps: $workflow->sequenceFor($project->status_code),
            current: $current,
            next: $next,
            previous: $previous,
            canAdvance: $canAdvance,
            canRevert: $canRevert,
            nextCrossesLock: $nextCrossesLock,
            prevUnlocks: $prevUnlocks,
            advanceBlockedReasons: $advanceBlockedReasons,
            revertBlockedReasons: $revertBlockedReasons,
            scheduled: self::scheduledTriggers($project, $current),
            history: $project->statusTransitions()->with('user')->limit(50)->get(),
            nextActions: $nextActions,
        );
    }

    /** Zeitgesteuerte Trigger des aktuellen Schritts, deren Zeitpunkt noch aussteht – für den Hinweis im Wizard. */
    protected static function scheduledTriggers(Project $project, ?WorkflowStatus $current): array
    {
        if (! $current) {
            return [];
        }

        $result = [];
        foreach ($current->triggers()->where('is_active', true)->with('actions')->get() as $trigger) {
            $type = \App\Services\Workflow\Events\WorkflowEventTypes::find($trigger->event);
            $at   = $type?->scheduled() ? $type->occurredAt($project) : null;
            if (! $at || ! $at->isFuture()) {
                continue;
            }

            $result[] = [
                'when'    => $at,
                'event'   => $type->label(),
                'actions' => $trigger->actions->where('is_active', true)
                    ->map(fn ($a) => WorkflowActionTypes::find($a->type)?->summary($a->config ?? []))
                    ->filter()->values()->all(),
            ];
        }

        usort($result, fn ($a, $b) => $a['when'] <=> $b['when']);

        return $result;
    }

    public function stepNumber(): int
    {
        $index = $this->steps->search(fn (WorkflowStatus $s) => $s->code === $this->project->status_code);

        return $index === false ? 1 : $index + 1;
    }

    public function totalSteps(): int
    {
        return $this->steps->count();
    }

    public function isLast(): bool
    {
        return $this->next === null;
    }
}
