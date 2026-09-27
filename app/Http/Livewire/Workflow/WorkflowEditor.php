<?php

namespace App\Http\Livewire\Workflow;

use App\Models\Project;
use App\Models\WorkflowAction;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStatus;
use App\Services\Workflow\Actions\WorkflowActionTypes;
use App\Services\Workflow\WorkflowColors;
use App\Services\Workflow\WorkflowDefaults;
use App\Services\Workflow\WorkflowRegistry;
use App\Services\Workflow\WorkflowStepKinds;
use App\Services\Workflow\WorkflowValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Admin-Bildschirm "Workflow": Schritte je Projekttyp anlegen/sortieren/
 * deaktivieren/löschen, Freigeber je Schritt sowie das Fixierungs-Override
 * festlegen. Maßnahmen (Drag&Drop-E-Mail-Aktionen) folgen in Phase 2.
 */
class WorkflowEditor extends Component
{
    public string $projectType = 'product';
    public ?string $selectedStepId = null;
    public ?string $error = null;

    public string $formLabel = '';
    public string $formDescription = '';
    public string $formColor = 'gray';
    public ?string $formDefaultTab = null;
    public bool $formAutoAdvance = false;

    public function mount(): void
    {
        $this->authorizeManage();

        foreach (array_keys(Project::TYPES) as $type) {
            WorkflowDefaults::ensureSeeded($type);
        }
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasPermission('workflow.manage'), 403);
    }

    private function selectedStep(): ?WorkflowStatus
    {
        return $this->selectedStepId
            ? WorkflowStatus::where('cis_row_id', $this->selectedStepId)->where('project_type', $this->projectType)->first()
            : null;
    }

    public function setType(string $type): void
    {
        if (! array_key_exists($type, Project::TYPES)) {
            return;
        }
        $this->projectType   = $type;
        $this->selectedStepId = null;
        $this->error          = null;
    }

    public function selectStep(string $stepId): void
    {
        $status = WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)->first();
        if (! $status) {
            return;
        }

        $this->selectedStepId  = $stepId;
        $this->formLabel       = $status->label;
        $this->formDescription = (string) $status->description;
        $this->formColor       = $status->color;
        $this->formDefaultTab  = $status->default_tab;
        $this->formAutoAdvance = $status->auto_advance_on_due;
        $this->error           = null;
    }

    public function saveStep(): void
    {
        $this->authorizeManage();
        $status = $this->selectedStep();
        if (! $status) {
            return;
        }

        $this->validate([
            'formLabel'      => 'required|string|max:100',
            'formColor'      => 'required|string|in:' . implode(',', WorkflowColors::PALETTE),
            'formDefaultTab' => 'nullable|string|in:' . implode(',', array_keys(WorkflowDefaults::TABS)),
        ]);

        $status->update([
            'label'               => $this->formLabel,
            'description'         => $this->formDescription !== '' ? $this->formDescription : null,
            'color'               => $this->formColor,
            'default_tab'         => $this->formDefaultTab,
            'auto_advance_on_due' => WorkflowStepKinds::allowsAutoAdvance($status->kind) ? $this->formAutoAdvance : false,
        ]);

        $this->error = null;
    }

    public function toggleActive(string $stepId): void
    {
        $this->authorizeManage();
        $status = WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)->first();
        if (! $status || $status->isRequired()) {
            return;
        }
        $status->update(['is_active' => ! $status->is_active]);
    }

    public function setApprovalMode(string $stepId, string $mode): void
    {
        $this->authorizeManage();
        if (! in_array($mode, ['anyone', 'restricted'], true)) {
            return;
        }
        WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)
            ->update(['approval_mode' => $mode]);
    }

    /**
     * Fügt einen Schritt hinzu und setzt ihn automatisch an die einzige gültige
     * Stelle bezüglich seines Rangs ein (siehe WorkflowStepKinds) – kein
     * manuelles Ablegen an der "richtigen" Stelle nötig, WorkflowValidator
     * prüft das Ergebnis trotzdem sicherheitshalber.
     */
    public function addStep(string $kind): void
    {
        $this->authorizeManage();
        $this->error = null;

        $steps = WorkflowStatus::where('project_type', $this->projectType)->orderBy('sort_order')->get();

        if ($kind !== 'custom') {
            if (! WorkflowStepKinds::find($kind)) {
                return;
            }
            if (! WorkflowStepKinds::isRequired($kind) && $steps->contains('kind', $kind)) {
                $this->error = 'Dieser Schritt ist bereits vorhanden.';
                return;
            }
            if (! WorkflowStepKinds::isModuleEnabled($kind)) {
                $this->error = 'Das zugehörige Modul ist nicht aktiv.';
                return;
            }
        }

        $rank        = WorkflowStepKinds::rank($kind);
        $insertIndex = $steps->count();
        foreach ($steps as $i => $s) {
            if ($s->kind !== 'custom' && WorkflowStepKinds::rank($s->kind) > $rank) {
                $insertIndex = $i;
                break;
            }
        }

        $candidate = $steps->map(fn (WorkflowStatus $s) => ['kind' => $s->kind])->values()->all();
        array_splice($candidate, $insertIndex, 0, [['kind' => $kind]]);

        $errors = WorkflowValidator::errors($candidate);
        if (! empty($errors)) {
            $this->error = $errors[0];
            return;
        }

        DB::transaction(function () use ($steps, $insertIndex, $kind) {
            foreach ($steps->slice($insertIndex)->values() as $i => $s) {
                $s->update(['sort_order' => $insertIndex + 1 + $i]);
            }

            $new = WorkflowStatus::create([
                'project_type'        => $this->projectType,
                'code'                => $kind === 'custom' ? 'custom_' . Str::lower(Str::random(6)) : $kind,
                'kind'                => $kind,
                'label'               => $kind === 'custom' ? 'Neuer Schritt' : WorkflowStepKinds::label($kind),
                'description'         => null,
                'color'               => WorkflowStepKinds::defaultColor($kind),
                'sort_order'          => $insertIndex,
                'is_active'           => true,
                'approval_mode'       => 'restricted',
                'auto_advance_on_due' => false,
                'default_tab'         => WorkflowStepKinds::defaultTab($kind),
            ]);

            $this->selectStep($new->cis_row_id);
        });
    }

    /** Server bleibt Autorität: bei einer ungültigen Reihenfolge wird nichts gespeichert und der Client setzt die Anzeige zurück. */
    public function reorderSteps(array $orderedIds): void
    {
        $this->authorizeManage();

        $steps     = WorkflowStatus::where('project_type', $this->projectType)->get()->keyBy('cis_row_id');
        $candidate = collect($orderedIds)->map(fn ($id) => ['kind' => $steps[$id]->kind ?? 'custom'])->all();

        $errors = WorkflowValidator::errors($candidate);
        if (! empty($errors)) {
            $this->error = $errors[0];
            return;
        }

        $this->error = null;
        foreach ($orderedIds as $index => $id) {
            WorkflowStatus::where('cis_row_id', $id)->where('project_type', $this->projectType)
                ->update(['sort_order' => $index]);
        }
        WorkflowRegistry::flush();
    }

    public function deleteStep(string $stepId): void
    {
        $this->authorizeManage();
        $status = WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)->first();
        if (! $status) {
            return;
        }
        if ($status->isRequired()) {
            $this->error = 'Dieser Schritt ist Pflichtbestandteil des Workflows und kann nicht gelöscht werden.';
            return;
        }
        $used = $status->usageCount();
        if ($used > 0) {
            $this->error = "Dieser Schritt wird noch von {$used} Projekt(en) verwendet und kann nicht gelöscht werden – stattdessen deaktivieren.";
            return;
        }

        $this->error = null;
        WorkflowApprover::where('cis_row_id_workflow_status', $stepId)->delete();
        $status->delete();

        if ($this->selectedStepId === $stepId) {
            $this->selectedStepId = null;
        }
    }

    #[On('principal-picked')]
    public function addPrincipal(string $context, string $type, string $id): void
    {
        $this->authorizeManage();

        if (! in_array($type, ['user', 'group', 'role'], true)) {
            return;
        }

        if ($context === 'lock_override') {
            WorkflowApprover::firstOrCreate([
                'project_type'               => $this->projectType,
                'scope'                      => 'lock_override',
                'cis_row_id_workflow_status' => null,
                'principal_type'             => $type,
                'principal_id'               => $id,
            ]);
            return;
        }

        if (str_starts_with($context, 'transition:')) {
            $stepId = substr($context, strlen('transition:'));
            if ($stepId !== $this->selectedStepId) {
                return;
            }
            WorkflowApprover::firstOrCreate([
                'project_type'               => $this->projectType,
                'scope'                      => 'transition',
                'cis_row_id_workflow_status' => $stepId,
                'principal_type'             => $type,
                'principal_id'               => $id,
            ]);
        }
    }

    public function removeApprover(string $approverId): void
    {
        $this->authorizeManage();
        WorkflowApprover::where('cis_row_id', $approverId)->where('project_type', $this->projectType)->delete();
    }

    /**
     * Legt eine neue, noch inaktive Maßnahme am per $stepId übergebenen Schritt an (dem
     * Ablageziel des Drag&Drop auf der Karte, NICHT notwendigerweise dem gerade
     * ausgewählten Schritt) und öffnet direkt den Editor dafür.
     */
    public function addActionAt(string $stepId, string $type, int $position): void
    {
        $this->authorizeManage();
        $step       = WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)->first();
        $actionType = WorkflowActionTypes::find($type);
        if (! $step || ! $actionType) {
            return;
        }

        $existing = WorkflowAction::where('cis_row_id_workflow_status', $step->cis_row_id)->orderBy('sort_order')->get();
        foreach ($existing->slice($position)->values() as $i => $a) {
            $a->update(['sort_order' => $position + 1 + $i]);
        }

        $action = WorkflowAction::create([
            'cis_row_id_workflow_status' => $step->cis_row_id,
            'trigger'                    => 'enter',
            'type'                       => $type,
            'sort_order'                 => $position,
            'is_active'                  => false,
            'config'                     => $actionType->defaultConfig(),
        ]);

        $this->dispatch('edit-workflow-action', id: $action->cis_row_id);
    }

    public function reorderActions(array $orderedIds): void
    {
        $this->authorizeManage();
        foreach ($orderedIds as $index => $id) {
            WorkflowAction::where('cis_row_id', $id)->update(['sort_order' => $index]);
        }
    }

    public function editAction(string $actionId): void
    {
        $this->dispatch('edit-workflow-action', id: $actionId);
    }

    public function toggleActionActive(string $actionId): void
    {
        $this->authorizeManage();
        $action = WorkflowAction::find($actionId);
        if ($action) {
            $action->update(['is_active' => ! $action->is_active]);
        }
    }

    public function deleteAction(string $actionId): void
    {
        $this->authorizeManage();
        WorkflowAction::where('cis_row_id', $actionId)->delete();
    }

    /** Kein eigener Zustand nötig – der Event-Empfang allein löst das Re-Render aus, damit die Maßnahmen-Zusammenfassung nach dem Speichern im Modal sofort aktuell ist. */
    #[On('workflow-action-saved')]
    public function refreshAfterActionSave(): void
    {
    }

    public function render()
    {
        $steps = WorkflowStatus::where('project_type', $this->projectType)
            ->orderBy('sort_order')
            ->with(['approvers', 'actions'])
            ->get();

        $usedKinds      = $steps->pluck('kind')->reject(fn ($k) => $k === 'custom')->all();
        $availableKinds = WorkflowStepKinds::availableToAdd($usedKinds);

        $selected              = $this->selectedStep();
        $selectedWarnings      = $selected ? WorkflowValidator::warnings($selected) : [];
        $lockOverrideApprovers = WorkflowApprover::where('project_type', $this->projectType)
            ->where('scope', 'lock_override')->get();

        return view('livewire.workflow.workflow-editor', [
            'steps'                 => $steps,
            'availableKinds'        => $availableKinds,
            'selected'              => $selected,
            'selectedWarnings'      => $selectedWarnings,
            'actionTypes'           => WorkflowActionTypes::all(),
            'lockOverrideApprovers' => $lockOverrideApprovers,
            'projectTypes'          => Project::TYPES,
            'tabs'                  => WorkflowDefaults::TABS,
            'colors'                => WorkflowColors::PALETTE,
        ]);
    }
}
