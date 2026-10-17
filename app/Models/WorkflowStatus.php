<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use App\Services\Workflow\WorkflowColors;
use App\Services\Workflow\WorkflowRegistry;
use App\Services\Workflow\WorkflowStepKinds;
use Illuminate\Database\Eloquent\Model;

/**
 * Ein Schritt im Projekt-Workflow eines Projekttyps (ersetzt die frühere
 * hartkodierte Project::STATUSES/STATUS_ORDER). Siehe WorkflowStepKinds für
 * die feste Bedeutung von `kind` (Rang, Pflicht, Meilensteine).
 */
class WorkflowStatus extends Model
{
    use CisUuid;

    protected $table = 'workflow_statuses';

    protected $fillable = [
        'project_type', 'code', 'kind', 'label', 'description', 'color',
        'sort_order', 'is_active', 'approval_mode', 'default_tab',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => WorkflowRegistry::flush());
        static::deleted(fn () => WorkflowRegistry::flush());
    }

    public function approvers()
    {
        return $this->hasMany(WorkflowApprover::class, 'cis_row_id_workflow_status', 'cis_row_id')
            ->where('scope', 'transition');
    }

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class, 'cis_row_id_workflow_status', 'cis_row_id')
            ->where('trigger', 'enter')
            ->orderBy('sort_order');
    }

    public function triggers()
    {
        return $this->hasMany(WorkflowTrigger::class, 'cis_row_id_workflow_status', 'cis_row_id')
            ->orderBy('sort_order');
    }

    public function isCustom(): bool
    {
        return $this->kind === 'custom';
    }

    public function isRequired(): bool
    {
        return WorkflowStepKinds::isRequired($this->kind);
    }

    public function hasMilestone(string $milestone): bool
    {
        return WorkflowStepKinds::hasMilestone($this->kind, $milestone);
    }

    public function moduleEnabled(): bool
    {
        return WorkflowStepKinds::isModuleEnabled($this->kind);
    }

    /** Aktiv UND (falls modulgebunden) das zugehörige Modul aktiv. */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->moduleEnabled();
    }

    public function badgeClasses(): string
    {
        return WorkflowColors::badge($this->color);
    }

    /** Anzahl Projekte (inkl. Papierkorb), die aktuell auf diesem Schritt stehen. */
    public function usageCount(): int
    {
        return Project::withTrashed()
            ->where('type', $this->project_type)
            ->where('status_code', $this->code)
            ->count();
    }
}
