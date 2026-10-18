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
     * @param array<string, string> $fixations Fixierungsbereich (siehe FixationAction::SCOPES) => Code des Schritts, ab dem er fixiert ist
     */
    public function __construct(
        protected Collection $steps,
        protected Collection $lockOverrideApprovers,
        protected array $fixations = [],
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

    /**
     * Ist der Bereich (products|tender|offers|awarding) auf diesem Schritt fixiert? Fixiert ist, wer auf oder hinter dem
     * Schritt steht, der die Fixierungs-Maßnahme trägt – deshalb öffnet sich eine Fixierung beim Zurücksetzen von selbst wieder.
     */
    public function isFixed(?string $code, string $scope): bool
    {
        $carrier = $this->fixations[$scope] ?? null;
        if ($carrier === null) {
            return false;
        }
        $carrierIndex = $this->structuralIndex($carrier);
        $index        = $this->structuralIndex($code);

        return $index !== false && $carrierIndex !== false && $index >= $carrierIndex;
    }

    /** Produkte (inkl. Preisfixierung) fixiert? Die Standard-Frage "ist das Projekt gesperrt". */
    public function isLocked(?string $code): bool
    {
        return $this->isFixed($code, 'products');
    }

    /** @return string[] alle auf diesem Schritt fixierten Bereiche in logischer Reihenfolge */
    public function fixedScopesAt(?string $code): array
    {
        return array_values(array_filter(array_keys(Actions\FixationAction::SCOPES), fn (string $scope) => $this->isFixed($code, $scope)));
    }

    /** Bereiche, die genau diese Schrittfolge neu fixiert bzw. wieder öffnet. @return array{fixed: string[], opened: string[]} */
    public function fixationChange(?string $fromCode, ?string $toCode): array
    {
        $from = $this->fixedScopesAt($fromCode);
        $to   = $this->fixedScopesAt($toCode);

        return ['fixed' => array_values(array_diff($to, $from)), 'opened' => array_values(array_diff($from, $to))];
    }

    /** Bereiche, die dieser Schritt selbst (per Maßnahme) fixiert. @return string[] */
    public function fixationsCarriedBy(?string $code): array
    {
        return array_values(array_keys(array_filter($this->fixations, fn ($carrier) => $carrier === $code)));
    }

    /** Der Schritt, der diesen Meilenstein trägt (sofern sein Modul aktiv ist). */
    public function milestoneStep(string $milestone): ?WorkflowStatus
    {
        return $this->steps->first(fn (WorkflowStatus $s) => $s->hasMilestone($milestone) && $s->moduleEnabled());
    }

    public function hasReached(?string $code, string $milestone): bool
    {
        $milestoneStep = $this->milestoneStep($milestone);
        if (! $milestoneStep) {
            return false;
        }
        $milestoneIndex = $this->structuralIndex($milestoneStep->code);
        $index          = $this->structuralIndex($code);

        return $index !== false && $milestoneIndex !== false && $index >= $milestoneIndex;
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
