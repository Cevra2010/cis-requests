<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Modules\Lager\Models\Lagerort;
use Modules\Lager\Models\LagerortType;
use Modules\Lager\Models\LagerStock;
use Nwidart\Modules\Facades\Module;

/** Systemdatensatz "Lagerorte" (Modul Lager) inkl. Hierarchie, Typ, Projektzuordnung und Bestandssummen. */
class LagerortDataset extends Dataset
{
    public function key(): string
    {
        return 'lager_locations';
    }

    public function label(): string
    {
        return 'Lagerorte';
    }

    public function available(): bool
    {
        return Module::find('Lager')?->isEnabled() ?? false;
    }

    public function fields(): array
    {
        return [
            'lo_code'        => ['label' => 'Lagerort-ID (Code)',          'origin' => 'lager'],
            'lo_name'        => ['label' => 'Name',                        'origin' => 'lager', 'import' => true],
            'lo_type'        => ['label' => 'Typ',                         'origin' => 'lager', 'import' => true],
            'lo_type_abbr'   => ['label' => 'Typ-Kürzel',                  'origin' => 'lager'],
            'lo_number'      => ['label' => 'Laufende Nummer',             'origin' => 'lager'],
            'lo_parent'      => ['label' => 'Übergeordneter Lagerort (ID)', 'origin' => 'lager', 'import' => true, 'clearable' => true],
            'lo_path'        => ['label' => 'Pfad',                        'origin' => 'lager'],
            'lo_level'       => ['label' => 'Ebene',                       'origin' => 'lager'],
            'lo_sort'        => ['label' => 'Sortierung',                  'origin' => 'lager', 'import' => true],
            'lo_children'    => ['label' => 'Anzahl Unterlagerorte',       'origin' => 'lager'],
            'lo_projects'    => ['label' => 'Zugeordnete Projekte',        'origin' => 'projects'],
            'lo_stock_total' => ['label' => 'Bestand gesamt (Stück)',      'origin' => 'lager'],
            'lo_stock_items' => ['label' => 'Anzahl Produkte im Bestand',  'origin' => 'products'],
            'lo_created'     => ['label' => 'Angelegt am',                 'origin' => 'lager'],
            'lo_updated'     => ['label' => 'Zuletzt geändert am',         'origin' => 'lager'],
        ];
    }

    public function canCreate(): bool
    {
        return true;
    }

    protected function requiredForCreate(): array
    {
        return ['lo_name'];
    }

    protected function records(): iterable
    {
        return Lagerort::with(['type', 'parent.type', 'projects'])->orderBy('name')->get();
    }

    protected function exportValue(Model $l, string $field): string
    {
        return match ($field) {
            'lo_code'        => $l->code(),
            'lo_name'        => $l->name,
            'lo_type'        => (string) $l->type?->name,
            'lo_type_abbr'   => (string) $l->type?->abbreviation,
            'lo_number'      => (string) $l->number,
            'lo_parent'      => (string) $l->parent?->code(),
            'lo_path'        => $l->path(),
            'lo_level'       => (string) (substr_count($l->path('|'), '|') + 1),
            'lo_sort'        => (string) $l->sort_order,
            'lo_children'    => (string) $l->children()->count(),
            'lo_projects'    => $l->projects->pluck('name')->sort()->join('; '),
            'lo_stock_total' => (string) (int) LagerStock::where('cis_row_id_lagerort', $l->cis_row_id)->sum('quantity'),
            'lo_stock_items' => (string) LagerStock::where('cis_row_id_lagerort', $l->cis_row_id)->where('quantity', '>', 0)->distinct('cis_row_id_product')->count('cis_row_id_product'),
            'lo_created'     => $this->fmtDate($l->created_at, true),
            'lo_updated'     => $this->fmtDate($l->updated_at, true),
            default          => '',
        };
    }

    protected function findRecord(string $key): ?Model
    {
        return Lagerort::with(['type', 'parent'])->where('cis_row_id', $key)->first();
    }

    protected function newRecord(): Model
    {
        return new Lagerort();
    }

    protected function recordLabel(?Model $record, \Closure $get): string
    {
        return $record ? $record->code() . ' · ' . $record->name : ($get('lo_name') ?: 'neuer Lagerort');
    }

    protected function importField(?Model $l, string $field, string $raw, \Closure $get): ?array
    {
        switch ($field) {
            case 'lo_name':
                return $raw === ($l?->name) ? null : ['label' => 'Name', 'old' => (string) $l?->name, 'new' => $raw, 'set' => ['name' => $raw]];

            case 'lo_type':
                $type = LagerortType::all()->first(fn ($t) => mb_strtolower($t->name) === mb_strtolower($raw) || mb_strtolower($t->abbreviation) === mb_strtolower($raw));
                if (! $type) {
                    throw new DatasetImportException("Lagerort-Typ „{$raw}“ existiert nicht.");
                }
                if ($type->cis_row_id === $l?->cis_row_id_type) {
                    return null;
                }

                return [
                    'label' => 'Typ', 'old' => (string) $l?->type?->name, 'new' => $type->name,
                    'set'   => ['cis_row_id_type' => $type->cis_row_id],
                    'after' => fn (Lagerort $m) => $m->ensureUniqueNumber(),
                ];

            case 'lo_parent':
                $parent = $raw === '' ? null : $this->findParent($raw);
                if ($parent && $l && in_array($parent->cis_row_id, $l->selfAndDescendantIds(), true)) {
                    throw new DatasetImportException('Ein Lagerort kann nicht in sich selbst oder einem seiner Unterorte liegen.');
                }

                return $parent?->cis_row_id === $l?->cis_row_id_parent ? null : [
                    'label' => 'Übergeordneter Lagerort', 'old' => (string) $l?->parent?->code(), 'new' => (string) $parent?->code(),
                    'set'   => ['cis_row_id_parent' => $parent?->cis_row_id],
                    'after' => fn (Lagerort $m) => $m->ensureUniqueNumber(),
                ];

            case 'lo_sort':
                if (! ctype_digit($raw)) {
                    throw new DatasetImportException("Sortierung „{$raw}“ ist keine ganze Zahl.");
                }

                return (int) $raw === (int) $l?->sort_order ? null : ['label' => 'Sortierung', 'old' => (string) $l?->sort_order, 'new' => $raw, 'set' => ['sort_order' => (int) $raw]];
        }

        return null;
    }

    /** Übergeordneter Lagerort per Code (z.B. "H1") oder eindeutigem Namen. */
    private function findParent(string $raw): Lagerort
    {
        $all   = Lagerort::with('type')->get();
        $match = $all->filter(fn (Lagerort $o) => mb_strtolower($o->code()) === mb_strtolower($raw));
        if ($match->isEmpty()) {
            $match = $all->filter(fn (Lagerort $o) => mb_strtolower($o->name) === mb_strtolower($raw));
        }
        if ($match->count() !== 1) {
            throw new DatasetImportException($match->isEmpty()
                ? "Übergeordneter Lagerort „{$raw}“ existiert nicht."
                : "Übergeordneter Lagerort „{$raw}“ ist nicht eindeutig – bitte die Lagerort-ID verwenden.");
        }

        return $match->first();
    }
}
