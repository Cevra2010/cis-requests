<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Category;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Nwidart\Modules\Facades\Module;

/**
 * Systemdatensatz "Projekte". Der Status (Workflow) ist bewusst nur exportierbar – ein Statuswechsel läuft
 * immer über die Freigaben des Workflows. Neue Projekte starten wie üblich im ersten Workflow-Schritt.
 */
class ProjectDataset extends Dataset
{
    private const GROUP_PREFIX = 'Gruppe: ';

    public function key(): string
    {
        return 'projects';
    }

    public function label(): string
    {
        return 'Projekte';
    }

    public function fields(): array
    {
        return [
            'pj_id'          => ['label' => 'Projekt-ID',                 'origin' => 'projects'],
            'pj_name'        => ['label' => 'Projektname',                'origin' => 'projects', 'import' => true],
            'pj_description' => ['label' => 'Beschreibung',               'origin' => 'projects', 'import' => true, 'clearable' => true],
            'pj_type'        => ['label' => 'Projekttyp',                 'origin' => 'projects', 'import' => true],
            'pj_status'      => ['label' => 'Status',                     'origin' => 'projects'],
            'pj_category'    => ['label' => 'Kategorie',                  'origin' => 'categories', 'import' => true, 'clearable' => true],
            'pj_assignee'    => ['label' => 'Verantwortliche(r)',         'origin' => 'users', 'import' => true, 'clearable' => true],
            'pj_client'      => ['label' => 'Auftraggeber',               'origin' => 'projects', 'import' => true, 'clearable' => true],
            'pj_tender_year' => ['label' => 'Ausschreibungsjahr',         'origin' => 'projects', 'import' => true, 'clearable' => true],
            'pj_due_date'    => ['label' => 'Fälligkeitsdatum',           'origin' => 'projects', 'import' => true, 'clearable' => true],
            'pj_tender_start' => ['label' => 'Ausschreibungsbeginn',      'origin' => 'projects', 'import' => true, 'clearable' => true],
            'pj_tender_end'  => ['label' => 'Ausschreibungsende',         'origin' => 'projects', 'import' => true, 'clearable' => true],
            'pj_min_order'   => ['label' => 'Mindestbestellwert je Anbieter', 'origin' => 'projects', 'import' => true, 'clearable' => true],
            'pj_locked_at'   => ['label' => 'Fixiert am',                 'origin' => 'projects'],
            'pj_positions'   => ['label' => 'Anzahl Positionen',          'origin' => 'projects'],
            'pj_offers'      => ['label' => 'Anzahl Angebote',            'origin' => 'projects'],
            'pj_estimate'    => ['label' => 'Kostenschätzung (€)',        'origin' => 'prices'],
            'pj_lagerorte'   => ['label' => 'Zugeordnete Lagerorte',      'origin' => 'lager'],
            'pj_created'     => ['label' => 'Angelegt am',                'origin' => 'projects'],
            'pj_updated'     => ['label' => 'Zuletzt geändert am',        'origin' => 'projects'],
        ];
    }

    public function canCreate(): bool
    {
        return true;
    }

    protected function requiredForCreate(): array
    {
        return ['pj_name'];
    }

    protected function records(): iterable
    {
        return Project::withCount(['positions', 'offers'])->orderBy('name')->get();
    }

    protected function exportValue(Model $p, string $field): string
    {
        return match ($field) {
            'pj_id'           => $p->cis_row_id,
            'pj_name'         => $p->name,
            'pj_description'  => (string) $p->description,
            'pj_type'         => $p->typeLabel(),
            'pj_status'       => $p->statusLabel(),
            'pj_category'     => $p->category_id ? $p->categoryLabel() : '',
            'pj_assignee'     => $this->assigneeText($p),
            'pj_client'       => (string) $p->client,
            'pj_tender_year'  => (string) $p->tender_year,
            'pj_due_date'     => $this->fmtDate($p->due_date),
            'pj_tender_start' => $this->fmtDate($p->tender_starts_at, true),
            'pj_tender_end'   => $this->fmtDate($p->tender_ends_at, true),
            'pj_min_order'    => $p->min_order_value !== null ? number_format((float) $p->min_order_value, 2, ',', '') : '',
            'pj_locked_at'    => $this->fmtDate($p->tender_locked_at, true),
            'pj_positions'    => (string) $p->positions_count,
            'pj_offers'       => (string) $p->offers_count,
            'pj_estimate'     => number_format((float) $p->costEstimate()['total'], 2, ',', ''),
            'pj_lagerorte'    => Module::find('Lager')?->isEnabled() ? $p->lagerorte->pluck('name')->sort()->join('; ') : '',
            'pj_created'      => $this->fmtDate($p->created_at, true),
            'pj_updated'      => $this->fmtDate($p->updated_at, true),
            default           => '',
        };
    }

    private function assigneeText(Project $p): string
    {
        $a = $p->assignee();

        return match (true) {
            $a instanceof User  => $a->email ?: $a->name(),
            $a instanceof Group => self::GROUP_PREFIX . $a->name,
            default             => '',
        };
    }

    protected function findRecord(string $key): ?Model
    {
        return Project::where('cis_row_id', $key)->first();
    }

    protected function newRecord(): Model
    {
        return new Project();
    }

    protected function recordLabel(?Model $record, \Closure $get): string
    {
        return $record?->name ?? ($get('pj_name') ?: 'neues Projekt');
    }

    protected function importField(?Model $p, string $field, string $raw, \Closure $get): ?array
    {
        switch ($field) {
            case 'pj_name':
                return $raw === ($p?->name) ? null : ['label' => 'Projektname', 'old' => (string) $p?->name, 'new' => $raw, 'set' => ['name' => $raw]];

            case 'pj_description':
                return $raw === (string) $p?->description ? null : ['label' => 'Beschreibung', 'old' => (string) $p?->description, 'new' => $raw, 'set' => ['description' => $raw !== '' ? $raw : null]];

            case 'pj_type':
                $type = collect(Project::TYPES)->search(fn ($label, $key) => mb_strtolower($label) === mb_strtolower($raw) || $key === mb_strtolower($raw));
                if ($type === false) {
                    throw new DatasetImportException('Projekttyp „' . $raw . '“ ist unbekannt (' . implode(' oder ', Project::TYPES) . ').');
                }
                if ($p && $p->type !== $type) {
                    throw new DatasetImportException('Der Projekttyp lässt sich bei bestehenden Projekten nicht ändern.');
                }

                return $p ? null : ['label' => 'Projekttyp', 'old' => '', 'new' => Project::TYPES[$type], 'set' => ['type' => $type]];

            case 'pj_category':
                $category = $raw === '' ? null : Category::ofType('project.category')->get()->first(fn ($c) => mb_strtolower($c->name) === mb_strtolower($raw));
                if ($raw !== '' && ! $category) {
                    throw new DatasetImportException("Kategorie „{$raw}“ existiert nicht.");
                }

                return $category?->id === $p?->category_id ? null : ['label' => 'Kategorie', 'old' => $p?->category_id ? $p->categoryLabel() : '', 'new' => (string) $category?->name, 'set' => ['category_id' => $category?->id]];

            case 'pj_assignee':
                [$type, $id, $label] = $this->resolveAssignee($raw);
                $oldType = $p?->assignee_type;
                $oldId   = $p?->assignee_id;

                return ($type === $oldType && $id === $oldId) ? null : [
                    'label' => 'Verantwortliche(r)', 'old' => $p ? $this->assigneeText($p) : '', 'new' => $label,
                    'set'   => ['assignee_type' => $type, 'assignee_id' => $id],
                ];

            case 'pj_client':
                return $raw === (string) $p?->client ? null : ['label' => 'Auftraggeber', 'old' => (string) $p?->client, 'new' => $raw, 'set' => ['client' => $raw !== '' ? $raw : null]];

            case 'pj_tender_year':
                if ($raw !== '' && (! ctype_digit($raw) || (int) $raw < 2000 || (int) $raw > 2100)) {
                    throw new DatasetImportException("Ausschreibungsjahr „{$raw}“ ist ungültig (2000–2100).");
                }

                return $raw === (string) $p?->tender_year ? null : ['label' => 'Ausschreibungsjahr', 'old' => (string) $p?->tender_year, 'new' => $raw, 'set' => ['tender_year' => $raw !== '' ? (int) $raw : null]];

            case 'pj_due_date':
                $new = $raw === '' ? null : $this->parseDate($raw, 'Fälligkeitsdatum');

                return $new?->format('Y-m-d') === $p?->due_date?->format('Y-m-d') ? null : ['label' => 'Fälligkeitsdatum', 'old' => $this->fmtDate($p?->due_date), 'new' => $this->fmtDate($new), 'set' => ['due_date' => $new?->format('Y-m-d')]];

            case 'pj_tender_start':
            case 'pj_tender_end':
                $isStart = $field === 'pj_tender_start';
                $attr    = $isStart ? 'tender_starts_at' : 'tender_ends_at';
                $label   = $isStart ? 'Ausschreibungsbeginn' : 'Ausschreibungsende';
                $new     = $raw === '' ? null : $this->parseDate($raw, $label, true);

                if (! $isStart && $new) {
                    $startRaw = $get('pj_tender_start');
                    $start    = $startRaw !== null ? ($startRaw === '' ? null : $this->parseDate($startRaw, 'Ausschreibungsbeginn', true)) : $p?->tender_starts_at;
                    if ($start && $new->lessThanOrEqualTo($start)) {
                        throw new DatasetImportException('Das Ausschreibungsende muss nach dem Ausschreibungsbeginn liegen.');
                    }
                }

                return $new?->format('Y-m-d H:i') === $p?->{$attr}?->format('Y-m-d H:i') ? null : ['label' => $label, 'old' => $this->fmtDate($p?->{$attr}, true), 'new' => $this->fmtDate($new, true), 'set' => [$attr => $new?->format('Y-m-d H:i:s')]];

            case 'pj_min_order':
                $new = $raw === '' ? null : $this->parseDecimal($raw, 'Mindestbestellwert');
                if ($new !== null && $new < 0) {
                    throw new DatasetImportException('Mindestbestellwert darf nicht negativ sein.');
                }
                $old = $p?->min_order_value !== null ? (float) $p->min_order_value : null;

                return $new === $old ? null : ['label' => 'Mindestbestellwert', 'old' => $old !== null ? number_format($old, 2, ',', '.') : '', 'new' => $new !== null ? number_format($new, 2, ',', '.') : '', 'set' => ['min_order_value' => $new]];
        }

        return null;
    }

    /** "Gruppe: Name", E-Mail oder vollständiger Name → [type, id, Anzeige]; leer → [null, null, '']. */
    private function resolveAssignee(string $raw): array
    {
        if ($raw === '') {
            return [null, null, ''];
        }

        if (str_starts_with($raw, self::GROUP_PREFIX)) {
            $name  = trim(substr($raw, strlen(self::GROUP_PREFIX)));
            $group = Group::all()->first(fn ($g) => mb_strtolower($g->name) === mb_strtolower($name));
            if (! $group) {
                throw new DatasetImportException("Gruppe „{$name}“ existiert nicht.");
            }

            return ['group', $group->cis_row_id, self::GROUP_PREFIX . $group->name];
        }

        $matches = User::all()->filter(fn ($u) => mb_strtolower((string) $u->email) === mb_strtolower($raw) || mb_strtolower($u->name()) === mb_strtolower($raw));
        if ($matches->count() !== 1) {
            throw new DatasetImportException($matches->isEmpty() ? "Benutzer „{$raw}“ existiert nicht." : "Benutzer „{$raw}“ ist nicht eindeutig – bitte die E-Mail-Adresse verwenden.");
        }

        $user = $matches->first();

        return ['user', $user->cis_row_id, $user->email ?: $user->name()];
    }
}
