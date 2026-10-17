<?php

namespace App\Services\Workflow;

use App\Models\Project;
use App\Models\WorkflowApprover;
use App\Models\WorkflowAction;
use App\Models\WorkflowStatus;
use App\Models\WorkflowTrigger;
use Illuminate\Support\Facades\Schema;

/**
 * Seed-Daten (identisch zur früheren hartkodierten Project::STATUSES/
 * STATUS_ORDER, damit ein Deploy für niemanden etwas ändert) + Hilfsfunktionen
 * zum (Neu-)Anlegen eines Default-Workflows.
 */
class WorkflowDefaults
{
    public const TABS = [
        'beladung'      => 'Produkte',
        'ausschreibung' => 'Ausschreibung',
        'angebote'      => 'Angebote',
        'bestellung'    => 'Bestellung',
        'wareneingang'  => 'Wareneingang',
        'lager'         => 'Lager',
    ];

    /** @return array<int, array{kind:string,label:string,description:string,color:string,default_tab:string,jump_on_tender_end:bool,approval_mode:string}> */
    public static function seedSteps(): array
    {
        return [
            ['kind' => 'draft', 'label' => 'Entwurf', 'description' => 'Projekt wird vorbereitet.', 'color' => 'gray', 'default_tab' => 'beladung', 'jump_on_tender_end' => false, 'approval_mode' => 'anyone'],
            ['kind' => 'ready_for_tender', 'label' => 'Bereit zur Ausschreibung', 'description' => 'Vorbereitung abgeschlossen, die Ausschreibung kann eröffnet werden.', 'color' => 'blue', 'default_tab' => 'ausschreibung', 'jump_on_tender_end' => false, 'approval_mode' => 'restricted'],
            ['kind' => 'tender', 'label' => 'Ausschreibung', 'description' => 'Die Ausschreibung läuft; Produkte und Ausschreibungstext sind ab hier fixiert.', 'color' => 'amber', 'default_tab' => 'angebote', 'jump_on_tender_end' => true, 'approval_mode' => 'restricted'],
            ['kind' => 'ready_for_evaluation', 'label' => 'Bereit zur Auswertung', 'description' => 'Die Angebotsfrist ist abgelaufen, Angebote können ausgewertet werden.', 'color' => 'orange', 'default_tab' => 'angebote', 'jump_on_tender_end' => false, 'approval_mode' => 'restricted'],
            ['kind' => 'evaluated', 'label' => 'Ausgewertet', 'description' => 'Die Angebote sind ausgewertet, die Bestellung kann zugeordnet werden.', 'color' => 'cyan', 'default_tab' => 'bestellung', 'jump_on_tender_end' => false, 'approval_mode' => 'restricted'],
            ['kind' => 'ordered', 'label' => 'Bestellt', 'description' => 'Die Bestellungen sind ausgelöst.', 'color' => 'indigo', 'default_tab' => 'wareneingang', 'jump_on_tender_end' => false, 'approval_mode' => 'restricted'],
            ['kind' => 'orders_in_revision', 'label' => 'Bestellungen in Revision', 'description' => 'Bestellungen werden nachträglich angepasst.', 'color' => 'red', 'default_tab' => 'wareneingang', 'jump_on_tender_end' => false, 'approval_mode' => 'restricted'],
            ['kind' => 'completed', 'label' => 'Abgeschlossen', 'description' => 'Das Projekt ist abgeschlossen.', 'color' => 'green', 'default_tab' => 'wareneingang', 'jump_on_tender_end' => false, 'approval_mode' => 'restricted'],
        ];
    }

    public static function ensureSeeded(string $projectType): void
    {
        if (WorkflowStatus::where('project_type', $projectType)->exists()) {
            return;
        }

        // Frische Installationen durchlaufen die Seed-Migration noch VOR der Umstellung auf Trigger:
        // das alte Flag (falls die Spalte noch existiert) wird dort in einen Trigger umgewandelt.
        $legacyFlag = Schema::hasColumn('workflow_statuses', 'auto_advance_on_due');

        foreach (self::seedSteps() as $i => $step) {
            $status = WorkflowStatus::create([
                'project_type'  => $projectType,
                'code'          => $step['kind'],
                'kind'          => $step['kind'],
                'label'         => $step['label'],
                'description'   => $step['description'],
                'color'         => $step['color'],
                'sort_order'    => $i,
                'is_active'     => true,
                'approval_mode' => $step['approval_mode'],
                'default_tab'   => $step['default_tab'],
            ]);

            if ($legacyFlag && $step['jump_on_tender_end']) {
                \Illuminate\Support\Facades\DB::table('workflow_statuses')->where('cis_row_id', $status->cis_row_id)->update(['auto_advance_on_due' => true]);
            }

            // Standard wie bisher: Nach Ende des Ausschreibungszeitraums geht es automatisch weiter.
            if (! $legacyFlag && $step['jump_on_tender_end'] && Schema::hasTable('workflow_triggers')) {
                $trigger = WorkflowTrigger::create([
                    'cis_row_id_workflow_status' => $status->cis_row_id,
                    'event'                      => 'tender.ended',
                    'is_active'                  => true,
                    'sort_order'                 => 0,
                ]);
                WorkflowAction::create([
                    'cis_row_id_workflow_status'  => $status->cis_row_id,
                    'cis_row_id_workflow_trigger' => $trigger->cis_row_id,
                    'trigger'                     => 'event',
                    'type'                        => 'goto_step',
                    'sort_order'                  => 0,
                    'is_active'                   => true,
                    'config'                      => ['target' => 'next'],
                ]);
            }
        }
    }

    /** In-Memory-Fallback (keine DB-Schreibung) – nur falls die Tabellen ausnahmsweise noch nicht existieren. Fail-closed: alle Schritte ohne Freigeber. */
    public static function inMemoryFallback(string $projectType): WorkflowDefinition
    {
        $steps = collect(self::seedSteps())->values()->map(function (array $step, int $i) use ($projectType) {
            $status = new WorkflowStatus([
                'project_type'        => $projectType,
                'code'                => $step['kind'],
                'kind'                => $step['kind'],
                'label'               => $step['label'],
                'description'         => $step['description'],
                'color'               => $step['color'],
                'sort_order'          => $i,
                'is_active'           => true,
                'approval_mode'       => 'restricted',
                'default_tab'         => $step['default_tab'],
            ]);
            $status->setRelation('approvers', collect());

            return $status;
        });

        return new WorkflowDefinition($steps, collect());
    }

    /**
     * Trägt eine Rolle als Freigeber für alle "restricted"-Schritte + das
     * Fixierungs-Override ein, für jeden bekannten Projekttyp. Genutzt vom
     * Setup-Assistenten, damit ein frisch angelegter Administrator sofort
     * freigeben kann.
     */
    public static function grantRoleEverywhere(string $roleId): void
    {
        foreach (array_keys(Project::TYPES) as $type) {
            self::ensureSeeded($type);

            $restrictedIds = WorkflowStatus::where('project_type', $type)
                ->where('approval_mode', 'restricted')
                ->pluck('cis_row_id');

            foreach ($restrictedIds as $statusId) {
                WorkflowApprover::firstOrCreate([
                    'project_type'               => $type,
                    'scope'                      => 'transition',
                    'cis_row_id_workflow_status' => $statusId,
                    'principal_type'             => 'role',
                    'principal_id'               => $roleId,
                ]);
            }

            WorkflowApprover::firstOrCreate([
                'project_type'               => $type,
                'scope'                      => 'lock_override',
                'cis_row_id_workflow_status' => null,
                'principal_type'             => 'role',
                'principal_id'               => $roleId,
            ]);
        }

        WorkflowRegistry::flush();
    }
}
