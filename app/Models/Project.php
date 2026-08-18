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
        'draft'                => ['label' => 'Entwurf',                   'badge' => 'bg-gray-100 text-gray-600'],
        'ready_for_tender'     => ['label' => 'Bereit zur Ausschreibung',   'badge' => 'bg-blue-100 text-blue-700'],
        'tender'               => ['label' => 'Ausschreibung',             'badge' => 'bg-amber-100 text-amber-700'],
        'ready_for_evaluation' => ['label' => 'Bereit zur Auswertung',     'badge' => 'bg-orange-100 text-orange-700'],
        'evaluated'            => ['label' => 'Ausgewertet',               'badge' => 'bg-cyan-100 text-cyan-700'],
        'ordered'              => ['label' => 'Bestellt',                  'badge' => 'bg-indigo-100 text-indigo-700'],
        'orders_in_revision'   => ['label' => 'Bestellungen in Revision',  'badge' => 'bg-red-100 text-red-700'],
        'completed'            => ['label' => 'Abgeschlossen',             'badge' => 'bg-green-100 text-green-700'],
    ];

    /** Reihenfolge des Projekt-Workflows. */
    public const STATUS_ORDER = [
        'draft', 'ready_for_tender', 'tender', 'ready_for_evaluation',
        'evaluated', 'ordered', 'orders_in_revision', 'completed',
    ];

    /** Ab diesem Status (inklusive) gelten Produkte/Ausschreibungstext als fixiert. */
    public const LOCK_FROM_STATUS = 'tender';

    protected static function booted(): void
    {
        static::saving(function (Project $project) {
            if (! $project->isDirty('status_code')) {
                return;
            }

            $wasLocked = self::codeIsLocked($project->getOriginal('status_code'));
            $nowLocked = $project->isLocked();

            if (! $wasLocked && $nowLocked) {
                $project->tender_locked_at = now();
                $project->tender_locked_by = auth()->user()?->cis_row_id;
            } elseif ($wasLocked && ! $nowLocked) {
                $project->tender_locked_at = null;
                $project->tender_locked_by = null;
            }
        });
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status_code]['label'] ?? $this->status_code;
    }

    public function statusBadgeClasses(): string
    {
        return self::STATUSES[$this->status_code]['badge'] ?? 'bg-gray-100 text-gray-600';
    }

    public static function codeIsLocked(?string $code): bool
    {
        $lockIndex = array_search(self::LOCK_FROM_STATUS, self::STATUS_ORDER, true);
        $index     = $code !== null ? array_search($code, self::STATUS_ORDER, true) : false;

        return $index !== false && $index >= $lockIndex;
    }

    public function nextStatusCode(): ?string
    {
        $index = array_search($this->status_code, self::STATUS_ORDER, true);

        return $index !== false ? (self::STATUS_ORDER[$index + 1] ?? null) : null;
    }

    public function previousStatusCode(): ?string
    {
        $index = array_search($this->status_code, self::STATUS_ORDER, true);

        return $index !== false && $index > 0 ? self::STATUS_ORDER[$index - 1] : null;
    }

    /** Rückt das Projekt einen Workflow-Schritt weiter. */
    public function advanceStatus(): void
    {
        $next = $this->nextStatusCode();
        if ($next) {
            $this->update(['status_code' => $next]);
        }
    }

    /** Setzt das Projekt einen Workflow-Schritt zurück. */
    public function revertStatus(): void
    {
        $prev = $this->previousStatusCode();
        if ($prev) {
            $this->update(['status_code' => $prev]);
        }
    }

    /** Springt automatisch auf "Bereit zur Auswertung", sobald der Ausschreibungszeitraum überschritten ist. */
    public function syncAutoStatus(): void
    {
        if ($this->status_code === 'tender' && $this->due_date && $this->due_date->isPast()) {
            $this->update(['status_code' => 'ready_for_evaluation']);
        }
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
    // Die Fixierung hängt am Status (siehe LOCK_FROM_STATUS) – tender_locked_at/_by
    // werden automatisch beim Statuswechsel gepflegt (siehe booted()).

    public function isLocked(): bool
    {
        return self::codeIsLocked($this->status_code);
    }

    public function lockedByUser(): ?User
    {
        return $this->tender_locked_by ? User::find($this->tender_locked_by) : null;
    }

    /** Darf $user Produkte/Ausschreibungstext dieses Projekts aktuell bearbeiten? */
    public function isEditableBy(?User $user): bool
    {
        if (! $this->isLocked()) {
            return true;
        }
        return $user?->hasPermission(self::OVERRIDE_LOCK_PERMISSION, $this->cis_row_id) ?? false;
    }
}
