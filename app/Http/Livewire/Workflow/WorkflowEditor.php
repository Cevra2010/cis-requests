<?php

namespace App\Http\Livewire\Workflow;

use App\Models\Project;
use App\Models\WorkflowAction;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStatus;
use App\Models\WorkflowTrigger;
use App\Services\Workflow\Actions\FixationAction;
use App\Services\Workflow\Actions\WorkflowActionTypes;
use App\Services\Workflow\Events\WorkflowEventTypes;
use App\Services\Workflow\WorkflowColors;
use App\Services\Workflow\WorkflowDefaults;
use App\Services\Workflow\WorkflowRegistry;
use App\Services\Workflow\WorkflowStepKinds;
use App\Services\Workflow\WorkflowTabs;
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

    /** Zählt Tab-Umschaltungen hoch, damit die Checkboxen neu gerendert werden – auch wenn der Server ein Umschalten ablehnt (sonst bliebe der Haken im Browser stehen). */
    public int $tabRevision = 0;

    public string $formLabel = '';
    public string $formDescription = '';
    public string $formColor = 'gray';
    public ?string $formDefaultTab = null;

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
     * Blendet einen Tab auf einem Schritt aus bzw. wieder ein. Nur logisch verfügbare Tabs lassen sich schalten,
     * und mindestens ein Tab muss sichtbar bleiben (sonst hätte die Projektseite nichts mehr zu zeigen).
     */
    public function toggleHiddenTab(string $stepId, string $tab): void
    {
        $this->authorizeManage();
        $this->tabRevision++;
        $step = WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)->first();
        if (! $step || ! in_array($tab, WorkflowTabs::keys(), true)) {
            return;
        }

        $availability = WorkflowTabs::availability(WorkflowRegistry::for($this->projectType), $step->code, $this->projectType);
        if (! ($availability[$tab]['available'] ?? false)) {
            return;
        }

        $hidden = $step->hiddenTabs();
        if (in_array($tab, $hidden, true)) {
            $hidden = array_values(array_diff($hidden, [$tab]));
        } else {
            $remaining = collect($availability)->filter(fn ($a, $key) => $a['available'] && $key !== $tab && ! in_array($key, $hidden, true));
            if ($remaining->isEmpty()) {
                $this->error = 'Mindestens ein Tab muss auf diesem Schritt sichtbar bleiben.';
                return;
            }
            $hidden[] = $tab;
        }

        $this->error = null;
        $step->update(['hidden_tabs' => $hidden ?: null]);
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

        // Eigene Schritte haben keinen festen Rang: sie kommen direkt hinter den gewählten
        // Schritt, sonst vor den (immer letzten) Schritt "Abgeschlossen" – danach per
        // Drag&Drop frei verschiebbar (die Reihenfolge-Prüfung greift weiterhin).
        if ($kind === 'custom') {
            $afterSelected = $this->selectedStepId ? $steps->search(fn (WorkflowStatus $s) => $s->cis_row_id === $this->selectedStepId) : false;
            $insertIndex   = $afterSelected !== false ? $afterSelected + 1 : $steps->count() - 1;
            $insertIndex   = max(1, min($insertIndex, $steps->count() - 1));
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
        if (empty($errors)) {
            $errors = WorkflowValidator::fixationErrors($this->fixationSteps($orderedIds), $this->projectType);
        }
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
        WorkflowAction::where('cis_row_id_workflow_status', $stepId)->delete();
        WorkflowTrigger::where('cis_row_id_workflow_status', $stepId)->delete();
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
    public function addActionAt(string $stepId, string $type, int $position, ?string $triggerId = null): void
    {
        $this->authorizeManage();
        $step       = WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)->first();
        $actionType = WorkflowActionTypes::find($type);
        if (! $step || ! $actionType) {
            return;
        }

        // Fixierungen gelten ab dem Erreichen eines Schritts – an einem Trigger hätten sie keinen Sinn – und müssen logisch passen.
        $scope = FixationAction::scopeOf($type);
        if ($scope !== null) {
            if ($triggerId) {
                $this->error = 'Fixierungen gelten ab dem Erreichen eines Schritts und lassen sich nicht an einen Trigger hängen.';
                return;
            }
            $errors = WorkflowValidator::fixationErrors($this->fixationSteps(null, $step->cis_row_id, $scope), $this->projectType);
            if (! empty($errors)) {
                $this->error = $errors[0];
                return;
            }
        }
        $this->error = null;

        // Ohne Trigger: Maßnahme beim Erreichen des Schritts; mit Trigger: Maßnahme dieses Triggers.
        $trigger = $triggerId
            ? WorkflowTrigger::where('cis_row_id', $triggerId)->where('cis_row_id_workflow_status', $step->cis_row_id)->first()
            : null;
        if ($triggerId && ! $trigger) {
            return;
        }

        $scope = WorkflowAction::where('cis_row_id_workflow_status', $step->cis_row_id)
            ->when($trigger,
                fn ($q) => $q->where('cis_row_id_workflow_trigger', $trigger->cis_row_id),
                fn ($q) => $q->where('trigger', 'enter'));
        $existing = $scope->orderBy('sort_order')->get();
        foreach ($existing->slice($position)->values() as $i => $a) {
            $a->update(['sort_order' => $position + 1 + $i]);
        }

        $action = WorkflowAction::create([
            'cis_row_id_workflow_status'  => $step->cis_row_id,
            'cis_row_id_workflow_trigger' => $trigger?->cis_row_id,
            'trigger'                    => $trigger ? 'event' : 'enter',
            'type'                       => $type,
            'sort_order'                 => $position,
            // Maßnahmen, die ohne weitere Angaben gültig sind (z.B. "Springen zu: nächster Schritt"), sind sofort aktiv;
            // alle anderen (z.B. E-Mail ohne Empfänger) werden erst durch Speichern im Editor aktiv.
            'is_active'                  => empty($actionType->validate($actionType->defaultConfig())),
            'config'                     => $actionType->defaultConfig(),
        ]);

        if ($actionType->editable()) {
            $this->dispatch('edit-workflow-action', id: $action->cis_row_id);
        }
    }

    /**
     * Schrittfolge mit den (aktiven) Fixierungs-Maßnahmen je Schritt für WorkflowValidator::fixationErrors() – optional in einer
     * anderen Reihenfolge oder mit einer zusätzlich angenommenen Fixierung, um eine Änderung vor dem Speichern zu prüfen.
     *
     * @return array<int, array{kind:string, label:string, scopes:string[]}>
     */
    private function fixationSteps(?array $orderIds = null, ?string $addToStep = null, ?string $addScope = null): array
    {
        $steps = WorkflowStatus::where('project_type', $this->projectType)->orderBy('sort_order')->with('actions')->get()->keyBy('cis_row_id');
        $order = $orderIds ?? $steps->keys()->all();

        $result = [];
        foreach ($order as $id) {
            $step = $steps[$id] ?? null;
            if (! $step) {
                continue;
            }
            $scopes = $step->actions->where('is_active', true)->map(fn ($a) => FixationAction::scopeOf($a->type))->filter()->values()->all();
            if ($id === $addToStep && $addScope !== null) {
                $scopes[] = $addScope;
            }
            $result[] = ['kind' => $step->kind, 'label' => $step->label, 'scopes' => $scopes];
        }

        return $result;
    }

    /** Legt einen Trigger (Ereignis) an einem Schritt an; seine Maßnahmen werden danach hineingezogen. */
    public function addTriggerAt(string $stepId, string $event): void
    {
        $this->authorizeManage();
        $step = WorkflowStatus::where('cis_row_id', $stepId)->where('project_type', $this->projectType)->first();
        if (! $step || ! WorkflowEventTypes::find($event)) {
            return;
        }

        WorkflowTrigger::create([
            'cis_row_id_workflow_status' => $step->cis_row_id,
            'event'                      => $event,
            'is_active'                  => true,
            'sort_order'                 => (WorkflowTrigger::where('cis_row_id_workflow_status', $step->cis_row_id)->max('sort_order') ?? -1) + 1,
        ]);
    }

    public function toggleTrigger(string $triggerId): void
    {
        $this->authorizeManage();
        $trigger = $this->ownTrigger($triggerId);
        $trigger?->update(['is_active' => ! $trigger->is_active]);
    }

    public function deleteTrigger(string $triggerId): void
    {
        $this->authorizeManage();
        $trigger = $this->ownTrigger($triggerId);
        if ($trigger) {
            WorkflowAction::where('cis_row_id_workflow_trigger', $trigger->cis_row_id)->delete();
            $trigger->delete();
        }
    }

    /** Trigger nur, wenn er an einem Schritt des aktuell bearbeiteten Projekttyps hängt. */
    private function ownTrigger(string $triggerId): ?WorkflowTrigger
    {
        $trigger = WorkflowTrigger::find($triggerId);
        $step    = $trigger ? WorkflowStatus::where('cis_row_id', $trigger->cis_row_id_workflow_status)->where('project_type', $this->projectType)->first() : null;

        return $step ? $trigger : null;
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
        $action = WorkflowAction::find($actionId);
        if ($action && (WorkflowActionTypes::find($action->type)?->editable() ?? false)) {
            $this->dispatch('edit-workflow-action', id: $actionId);
        }
    }

    public function toggleActionActive(string $actionId): void
    {
        $this->authorizeManage();
        $action = WorkflowAction::find($actionId);
        if (! $action) {
            return;
        }

        // Das Aktivieren einer Fixierung muss wie das Anlegen logisch passen.
        $scope = FixationAction::scopeOf($action->type);
        if ($scope !== null && ! $action->is_active) {
            $step   = WorkflowStatus::find($action->cis_row_id_workflow_status);
            $errors = WorkflowValidator::fixationErrors($this->fixationSteps(null, $step?->cis_row_id, $scope), $this->projectType);
            if (! empty($errors)) {
                $this->error = $errors[0];
                return;
            }
        }
        $this->error = null;

        $action->update(['is_active' => ! $action->is_active]);
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
            ->with(['approvers', 'actions', 'triggers.actions'])
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
            'eventTypes'            => WorkflowEventTypes::all(),
            'fixedByStep'           => $steps->mapWithKeys(fn ($s) => [$s->code => WorkflowRegistry::for($this->projectType)->fixedScopesAt($s->code)])->all(),
            'fixationScopes'        => FixationAction::SCOPES,
            'lockOverrideApprovers' => $lockOverrideApprovers,
            'projectTypes'          => Project::TYPES,
            'tabs'                  => WorkflowDefaults::TABS,
            'tabAvailability'       => $selected ? WorkflowTabs::availability(WorkflowRegistry::for($this->projectType), $selected->code, $this->projectType) : [],
            'colors'                => WorkflowColors::PALETTE,
        ]);
    }
}
