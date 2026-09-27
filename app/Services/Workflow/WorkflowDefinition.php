<?php

namespace App\Services\Workflow;

use App\Models\User;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStatus;
use Illuminate\Support\Collection;

/**
 * Reine, DB-freie Sicht auf den Workflow EINES Projekttyps: Reihenfolge,
 * Sperr-/Meilenstein-Logik, Freigabe-Prüfung. Project::workflow() liefert dies
 * über WorkflowRegistry; praktisch alle Status-Methoden von Project delegieren
 * hierher.
 */
class WorkflowDefinition
{
    /**
     * @param Collection<int, WorkflowStatus> $steps Strukturelle Reihenfolge, inkl. inaktiver Schritte.
     * @param Collection<int, WorkflowApprover> $lockOverrideApprovers
     */
    public function __construct(
        protected Collection $steps,
        protected Collection $lockOverrideApprovers,
    ) {
    }

    public function steps(): Collection
    {
        return $this->steps;
    }

    public function step(?string $code): ?WorkflowStatus
    {
        if ($code === null) {
            return null;
        }

        return $this->steps->first(fn (WorkflowStatus $s) => $s->code === $code);
    }

    public function initialCode(): string
    {
        return $this->steps->first()?->code ?? 'draft';
    }

    /** Aktive & verfügbare Schritte, plus den aktuellen Schritt selbst (auch falls inzwischen deaktiviert/nicht verfügbar). */
    public function sequenceFor(?string $currentCode): Collection
    {
        return $this->steps
            ->filter(fn (WorkflowStatus $s) => $s->isAvailable() || $s->code === $currentCode)
            ->values();
    }

    protected function indexIn(Collection $sequence, ?string $code): int|false
    {
        return $sequence->search(fn (WorkflowStatus $s) => $s->code === $code);
    }

    public function next(?string $code): ?WorkflowStatus
    {
        $seq   = $this->sequenceFor($code);
        $index = $this->indexIn($seq, $code);

        return $index !== false ? $seq->get($index + 1) : null;
    }

    public function previous(?string $code): ?WorkflowStatus
    {
        $seq   = $this->sequenceFor($code);
        $index = $this->indexIn($seq, $code);

        return ($index !== false && $index > 0) ? $seq->get($index - 1) : null;
    }

    /** Struktureller Index über ALLE Schritte (nicht nur aktive) – Sperr-/Meilenstein-Vergleiche bleiben so vom Aktiv-Status unabhängig. */
    protected function structuralIndex(?string $code): int|false
    {
        return $this->steps->search(fn (WorkflowStatus $s) => $s->code === $code);
    }

    public function isLocked(?string $code): bool
    {
        $lockStep = $this->steps->first(fn (WorkflowStatus $s) => $s->hasMilestone(WorkflowStepKinds::MILESTONE_LOCK));
        if (! $lockStep) {
            return false;
        }
        $lockIndex = $this->structuralIndex($lockStep->code);
        $index     = $this->structuralIndex($code);

        return $index !== false && $lockIndex !== false && $index >= $lockIndex;
    }

    public function hasReached(?string $code, string $milestone): bool
    {
        $milestoneStep = $this->steps->first(fn (WorkflowStatus $s) => $s->hasMilestone($milestone) && $s->moduleEnabled());
        if (! $milestoneStep) {
            return false;
        }
        $milestoneIndex = $this->structuralIndex($milestoneStep->code);
        $index          = $this->structuralIndex($code);

        return $index !== false && $milestoneIndex !== false && $index >= $milestoneIndex;
    }

    /** Ziel des Auto-Vorrückens, falls der aktuelle Schritt das Flag trägt (Fristprüfung selbst bleibt bei Project::syncAutoStatus()). */
    public function autoAdvanceTarget(?string $code): ?WorkflowStatus
    {
        $current = $this->step($code);
        if (! $current || ! $current->auto_advance_on_due) {
            return null;
        }

        return $this->next($code);
    }

    protected function principalMatches(WorkflowApprover $approver, User $user): bool
    {
        return match ($approver->principal_type) {
            'user'  => $approver->principal_id === $user->cis_row_id,
            'group' => $user->groups->contains('cis_row_id', $approver->principal_id),
            'role'  => $user->roles->contains('cis_row_id', $approver->principal_id),
            default => false,
        };
    }

    public function canApprove(?WorkflowStatus $step, ?User $user): bool
    {
        if (! $step || ! $user) {
            return false;
        }
        if ($step->approval_mode !== 'restricted') {
            return true;
        }
        foreach ($step->approvers as $approver) {
            if ($this->principalMatches($approver, $user)) {
                return true;
            }
        }

        return false;
    }

    public function canOverrideLock(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        foreach ($this->lockOverrideApprovers as $approver) {
            if ($this->principalMatches($approver, $user)) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    public function approverLabels(WorkflowStatus $step): array
    {
        if ($step->approval_mode !== 'restricted') {
            return ['Alle Benutzer'];
        }
        $labels = $step->approvers->map->label()->all();

        return $labels ?: ['Niemand (keine Freigeber hinterlegt)'];
    }

    /** @return string[] */
    public function lockOverrideLabels(): array
    {
        $labels = $this->lockOverrideApprovers->map->label()->all();

        return $labels ?: ['Niemand (keine Freigeber hinterlegt)'];
    }
}
