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
        'type',
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

    public const TYPES = [
        'product' => 'Produktausschreibung',
        'vehicle' => 'Fahrzeugausschreibung',
    ];

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

    /**
     * Berechtigung, die nötig ist, um die Grenze zu diesem Status zu überschreiten
     * (in beide Richtungen – vorrücken auf diesen Status oder von ihm zurückfallen
     * auf den vorherigen). "draft" ist der Startzustand und daher nicht gelistet.
     */
    public const STATUS_PERMISSIONS = [
        'ready_for_tender'     => 'project.status.set.ready_for_tender',
        'tender'                => 'project.status.set.tender',
        'ready_for_evaluation'  => 'project.status.set.ready_for_evaluation',
        'evaluated'             => 'project.status.set.evaluated',
        'ordered'               => 'project.status.set.ordered',
        'orders_in_revision'    => 'project.status.set.orders_in_revision',
        'completed'             => 'project.status.set.completed',
    ];

    /** @var string|null 'lock'|'unlock', gesetzt in saving(), ausgewertet in saved() */
    private ?string $pendingLockTransition = null;

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
                $project->pendingLockTransition = 'lock';
            } elseif ($wasLocked && ! $nowLocked) {
                $project->tender_locked_at = null;
                $project->tender_locked_by = null;
                $project->pendingLockTransition = 'unlock';
            }
        });

        static::saved(function (Project $project) {
            if ($project->pendingLockTransition === 'lock') {
                $project->snapshotPrices();
            } elseif ($project->pendingLockTransition === 'unlock') {
                $project->clearPriceSnapshots();
            }
            $project->pendingLockTransition = null;
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

    /** Berechtigung, die zum Vorrücken auf den nächsten Status nötig ist (falls es einen gibt). */
    public function nextTransitionPermission(): ?string
    {
        $next = $this->nextStatusCode();
        return $next ? (self::STATUS_PERMISSIONS[$next] ?? null) : null;
    }

    /** Berechtigung, die zum Zurückfallen auf den vorherigen Status nötig ist. */
    public function previousTransitionPermission(): ?string
    {
        return self::STATUS_PERMISSIONS[$this->status_code] ?? null;
    }

    public function canAdvanceStatus(?User $user): bool
    {
        if (! $this->nextStatusCode()) {
            return false;
        }
        $permission = $this->nextTransitionPermission();
        return $permission === null || ($user?->hasPermission($permission, $this->cis_row_id) ?? false);
    }

    public function canRevertStatus(?User $user): bool
    {
        if (! $this->previousStatusCode()) {
            return false;
        }
        $permission = $this->previousTransitionPermission();
        return $permission === null || ($user?->hasPermission($permission, $this->cis_row_id) ?? false);
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

    public function isVehicle(): bool
    {
        return $this->type === 'vehicle';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function vehicleBlocks()
    {
        return $this->hasMany(ProjectVehicleBlock::class, 'cis_row_id_project', 'cis_row_id')
            ->orderBy('sort_order');
    }

    public function categoryLabel(): string
    {
        if (! $this->category_id) {
            return '–';
        }
        return Category::find($this->category_id)?->name ?? 'Unbekannt';
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

    /**
     * Unterprodukte über alle Positionen dieses Projekts hinweg aggregiert (z.B.
     * "Neubauschlüssel" 2×, wenn zwei verschiedene Elternprodukte je 1× davon
     * enthalten). Einheitliche Grundlage für Angebotsvergleich, Zuordnung und
     * Bestelllisten – ein Unterprodukt hat projektweit genau eine Menge.
     *
     * @return \Illuminate\Support\Collection<int, array{product: Product, quantity: int}>
     */
    public function aggregatedChildPositions(): \Illuminate\Support\Collection
    {
        // Hausinterne Positionen (siehe ProjectProduct::is_internal) werden nicht
        // ausgeschrieben – ihre Unterprodukte gelten daher als bereits vorhanden
        // und tauchen entsprechend auch nicht in der Aggregation auf.
        $positions = $this->positions()->with('product.childs')->get()
            ->reject(fn (ProjectProduct $p) => $p->is_internal);

        return \App\Services\ChildProductAggregator::aggregate(
            $positions->map(fn (ProjectProduct $p) => ['product' => $p->product, 'quantity' => $p->product_count])
        );
    }

    /**
     * Positionen für die Materialliste (Ausschreibungstext-Editor & PDF-Export):
     * Setprodukte erscheinen nie als eigene Zeile, nur ihre Mitgliedsprodukte –
     * projektweit über ALLE Sets hinweg aggregiert. Kommt ein Mitgliedsprodukt
     * (z.B. "Neubauschlüssel") in mehreren Sets vor, erscheint es hier nur
     * einmal mit der Gesamtmenge (Beispiel: je 1× "Halligen Tool kurz (Set)"
     * und "Halligen Tool lang (Set)" ergeben 1× Halligen Tool kurz, 1× Halligen
     * Tool lang, 2× Neubauschlüssel, 2× Trageriemen).
     *
     * @return \Illuminate\Support\Collection<int, object{cis_row_id: string, name: string, is_set: bool, product_count: int, note: ?string}>
     */
    public function materialListItems(): \Illuminate\Support\Collection
    {
        $rawPositions = \Illuminate\Support\Facades\DB::table('project_product')
            ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
            ->where('project_product.cis_row_id_project', $this->cis_row_id)
            ->whereNull('products.deleted_at')
            ->where('project_product.is_internal', false)
            ->orderBy('project_product.sort_order')
            ->select('products.cis_row_id', 'products.name', 'products.is_set',
                     'project_product.product_count', 'project_product.note')
            ->get();

        $items           = collect();
        $setMemberTotals = [];

        foreach ($rawPositions as $rawPosition) {
            if (! $rawPosition->is_set) {
                $items->push($rawPosition);
                continue;
            }

            $members = \Illuminate\Support\Facades\DB::table('product_child')
                ->join('products', 'product_child.cis_row_id_child', '=', 'products.cis_row_id')
                ->where('product_child.cis_row_id_parent', $rawPosition->cis_row_id)
                ->whereNull('products.deleted_at')
                ->select('products.cis_row_id', 'products.name')
                ->get();

            foreach ($members as $member) {
                $setMemberTotals[$member->cis_row_id] ??= ['name' => $member->name, 'quantity' => 0];
                $setMemberTotals[$member->cis_row_id]['quantity'] += $rawPosition->product_count;
            }
        }

        foreach ($setMemberTotals as $id => $entry) {
            $items->push((object) [
                'cis_row_id'    => $id,
                'name'          => $entry['name'],
                'is_set'        => false,
                'product_count' => $entry['quantity'],
                'note'          => null,
            ]);
        }

        return $items->values();
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

    // ── Preis-Fixierung ──────────────────────────────────────────────────────
    // Sobald das Projekt fixiert wird, werden die aktuellen Katalogpreise aller
    // Positionen UND ihrer Unterprodukte eingefroren (siehe booted()). Ändert
    // sich der Katalogpreis danach, bleiben bereits fixierte Projekte
    // unverändert – effectivePrice()/effectiveGroupPrice() liefern dann den
    // eingefrorenen statt des aktuellen Preises.

    public function priceSnapshots()
    {
        return $this->hasMany(ProjectPriceSnapshot::class, 'cis_row_id_project', 'cis_row_id');
    }

    private function snapshotPrices(): void
    {
        $positions = $this->positions()->with(['product.childs'])->get();

        $products = collect();
        foreach ($positions as $position) {
            if (! $position->product) {
                continue;
            }
            $products->push($position->product);
            foreach ($position->product->childs as $child) {
                $products->push($child);
            }
        }

        foreach ($products->unique('cis_row_id') as $product) {
            $this->freezeProductPrice($product);
        }
    }

    private function clearPriceSnapshots(): void
    {
        $this->priceSnapshots()->delete();
    }

    private function freezeProductPrice(Product $product): ?ProjectPriceSnapshot
    {
        $price = $product->price();
        if (! $price) {
            return null;
        }

        return ProjectPriceSnapshot::updateOrCreate(
            ['cis_row_id_project' => $this->cis_row_id, 'cis_row_id_product' => $product->cis_row_id],
            [
                'amount'      => $price->amount,
                'source_name' => $price->source?->name,
                'frozen_at'   => now(),
            ]
        );
    }

    /**
     * Der für dieses Projekt maßgebliche Preis eines Produkts: solange das
     * Projekt nicht fixiert ist, der aktuelle Katalogpreis; danach der zum
     * Fixierungszeitpunkt eingefrorene Preis (wird bei Bedarf nachträglich
     * eingefroren, falls eine Position erst nach der Fixierung hinzukam).
     */
    public function effectivePrice(?Product $product): ?float
    {
        if (! $product) {
            return null;
        }

        if (! $this->isLocked()) {
            $price = $product->price();
            return $price ? (float) $price->amount : null;
        }

        $snapshot = $this->priceSnapshots()->where('cis_row_id_product', $product->cis_row_id)->first();
        if ($snapshot) {
            return (float) $snapshot->amount;
        }

        $snapshot = $this->freezeProductPrice($product);
        return $snapshot ? (float) $snapshot->amount : null;
    }

    /** Produkt-Eigenpreis + Unterprodukte, jeweils über effectivePrice() (fixierungsbewusst). */
    public function effectiveGroupPrice(Product $product): float
    {
        $amount = $this->effectivePrice($product) ?? 0.0;

        foreach ($product->getChild() as $child) {
            $amount += $this->effectivePrice($child) ?? 0.0;
        }

        return $amount;
    }

    /**
     * Eine Zeile je zugeordneter Produktposition (Menge, Einzelpreis,
     * Gesamtpreis), Basis sowohl für costEstimate() als auch für die
     * Projektübersicht-PDF. Hausinterne Positionen (bereits im Haus
     * vorhanden) verursachen keine Beschaffungskosten und werden ausgelassen.
     *
     * @return array<int, array{name: string, count: int, unit_price: float, line_total: ?float, has_price: bool}>
     */
    public function costEstimateLines(): array
    {
        $positions = ProjectProduct::where('cis_row_id_project', $this->cis_row_id)
            ->where('is_internal', false)
            ->with('product')
            ->orderBy('sort_order')
            ->get();

        $lines = [];

        foreach ($positions as $position) {
            if (! $position->product) {
                continue;
            }
            $unitPrice = $this->effectiveGroupPrice($position->product);
            $hasPrice  = $unitPrice > 0;

            $lines[] = [
                'name'       => $position->product->name,
                'count'      => $position->product_count,
                'unit_price' => $unitPrice,
                'line_total' => $hasPrice ? $unitPrice * $position->product_count : null,
                'has_price'  => $hasPrice,
            ];
        }

        return $lines;
    }

    /**
     * Grobe Kostenschätzung über alle zugeordneten Produktpositionen (Menge ×
     * effectiveGroupPrice), gemeinsam genutzt von der Produkte-Zuordnung und
     * dem Ausschreibungs-Editor – ein zentraler Berechnungsweg, damit beide
     * Stellen immer denselben Wert zeigen.
     */
    public function costEstimate(): array
    {
        $lines = $this->costEstimateLines();

        return [
            'total'           => array_sum(array_column(array_filter($lines, fn ($l) => $l['has_price']), 'line_total')),
            'positions_count' => count($lines),
            'missing_count'   => count(array_filter($lines, fn ($l) => ! $l['has_price'])),
            'fixed'           => $this->isLocked(),
        ];
    }
}
