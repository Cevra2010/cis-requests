<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, CisUuid;

    protected $fillable = [
        'tender_text',
        'name',
        'description',
        'status_code',
        'category_id',
        'assignee_type',
        'assignee_id',
        'client',
        'tender_year',
        'due_date',
        'min_order_value',
    ];

    protected $casts = [
        'due_date' => 'date',
        'min_order_value' => 'decimal:2',
        'tender_locked_at' => 'datetime',
    ];

    public const OVERRIDE_LOCK_PERMISSION = 'project.tender.override_lock';

    public const STATUSES = [
        'draft'     => ['label' => 'Entwurf',        'color' => 'gray'],
        'active'    => ['label' => 'Aktiv',           'color' => 'blue'],
        'review'    => ['label' => 'In Prüfung',      'color' => 'yellow'],
        'completed' => ['label' => 'Abgeschlossen',   'color' => 'green'],
        'archived'  => ['label' => 'Archiviert',      'color' => 'gray'],
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status_code]['label'] ?? $this->status_code;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status_code]['color'] ?? 'gray';
    }

    public function categoryLabel(): string
    {
        if (! $this->category_id) {
            return '–';
        }
        $cat = \DB::table('categories')->where('id', $this->category_id)->whereNull('deleted_at')->first();
        return $cat?->name ?? 'Unbekannt';
    }

    public function assignee()
    {
        if (! $this->assignee_type || ! $this->assignee_id) {
            return null;
        }
        $model = match ($this->assignee_type) {
            'user'  => User::find($this->assignee_id),
            'group' => Group::find($this->assignee_id),
            default => null,
        };
        return $model;
    }

    public function assigneeLabel(): string
    {
        $a = $this->assignee();
        if (! $a) {
            return '–';
        }
        if ($a instanceof User) {
            return $a->name();
        }
        return $a->name ?? '–';
    }

    public function tenderBlocks()
    {
        return $this->hasMany(ProjectTenderBlock::class, 'cis_row_id_project', 'cis_row_id')
            ->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'project_product',
            'cis_row_id_project',
            'cis_row_id_product',
            'cis_row_id',
            'cis_row_id'
        );
    }

    public function positions()
    {
        return $this->hasMany(ProjectProduct::class, 'cis_row_id_project', 'cis_row_id')
            ->orderBy('sort_order');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'cis_row_id_project', 'cis_row_id');
    }

    public function effectiveMinOrderValue(): float
    {
        return (float) ($this->min_order_value ?? Setting::get('default_min_order_value', 0));
    }

    // ── Ausschreibungs-Fixierung ─────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->tender_locked_at !== null;
    }

    public function lockedByUser(): ?User
    {
        return $this->tender_locked_by ? User::find($this->tender_locked_by) : null;
    }

    public function lock(User $user): void
    {
        $this->forceFill([
            'tender_locked_at' => now(),
            'tender_locked_by' => $user->cis_row_id,
        ])->save();
    }

    public function unlock(): void
    {
        $this->forceFill([
            'tender_locked_at' => null,
            'tender_locked_by' => null,
        ])->save();
    }

    /** Darf $user Produkte/Ausschreibungstext dieses Projekts aktuell bearbeiten? */
    public function isEditableBy(?User $user): bool
    {
        if (! $this->isLocked()) {
            return true;
        }
        return $user?->hasPermission(self::OVERRIDE_LOCK_PERMISSION, $this->cis_row_id) ?? false;
    }

    // Legacy helpers kept for backwards compatibility
    public function getStatusText(): string { return $this->statusLabel(); }
    public function getStatusColor(): string { return $this->statusColor(); }
}
