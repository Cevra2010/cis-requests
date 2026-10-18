<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Systemdatensatz "Gruppen" inkl. Mitgliedern und global erlaubten Berechtigungen. */
class GroupDataset extends Dataset
{
    use SyncsPermissions;

    public function key(): string
    {
        return 'groups';
    }

    public function label(): string
    {
        return 'Gruppen';
    }

    protected function permissionTable(): array
    {
        return ['group_permissions', 'group_id'];
    }

    public function fields(): array
    {
        return [
            'gr_id'           => ['label' => 'Gruppen-ID',                         'origin' => 'groups'],
            'gr_name'         => ['label' => 'Gruppenname',                        'origin' => 'groups', 'import' => true],
            'gr_description'  => ['label' => 'Beschreibung',                       'origin' => 'groups', 'import' => true, 'clearable' => true],
            'gr_color'        => ['label' => 'Farbe',                              'origin' => 'groups', 'import' => true, 'clearable' => true],
            'gr_members'      => ['label' => 'Mitglieder (E-Mail)',                'origin' => 'users', 'import' => true, 'clearable' => true],
            'gr_member_count' => ['label' => 'Anzahl Mitglieder',                  'origin' => 'groups'],
            'gr_permissions'  => ['label' => 'Berechtigungen (global)',            'origin' => 'groups', 'import' => true, 'clearable' => true],
            'gr_perm_count'   => ['label' => 'Anzahl Berechtigungen (global)',     'origin' => 'groups'],
            'gr_perm_project' => ['label' => 'Projektbezogene Berechtigungen (Anzahl)', 'origin' => 'groups'],
            'gr_perm_denied'  => ['label' => 'Verweigerte Berechtigungen (Anzahl)', 'origin' => 'groups'],
            'gr_created'      => ['label' => 'Angelegt am',                        'origin' => 'groups'],
            'gr_updated'      => ['label' => 'Zuletzt geändert am',                'origin' => 'groups'],
        ];
    }

    public function canCreate(): bool
    {
        return true;
    }

    protected function requiredForCreate(): array
    {
        return ['gr_name'];
    }

    protected function records(): iterable
    {
        return Group::with('users')->orderBy('name')->get();
    }

    protected function exportValue(Model $g, string $field): string
    {
        return match ($field) {
            'gr_id'           => $g->cis_row_id,
            'gr_name'         => $g->name,
            'gr_description'  => (string) $g->description,
            'gr_color'        => (string) $g->color,
            'gr_members'      => $g->users->pluck('email')->sort()->join('; '),
            'gr_member_count' => (string) $g->users->count(),
            'gr_permissions'  => implode('; ', $this->globalPermissionSlugs($g)),
            'gr_perm_count'   => (string) $this->permissionCount($g, false),
            'gr_perm_project' => (string) $this->permissionCount($g, true),
            'gr_perm_denied'  => (string) ($this->permissionCount($g, false, false) + $this->permissionCount($g, true, false)),
            'gr_created'      => $this->fmtDate($g->created_at, true),
            'gr_updated'      => $this->fmtDate($g->updated_at, true),
            default           => '',
        };
    }

    protected function findRecord(string $key): ?Model
    {
        return Group::with('users')->where('cis_row_id', $key)->first();
    }

    protected function newRecord(): Model
    {
        return new Group();
    }

    protected function recordLabel(?Model $record, \Closure $get): string
    {
        return $record?->name ?? ($get('gr_name') ?: 'neue Gruppe');
    }

    protected function importField(?Model $g, string $field, string $raw, \Closure $get): ?array
    {
        switch ($field) {
            case 'gr_name':
                if ($raw === ($g?->name)) {
                    return null;
                }
                if (Group::withTrashed()->where('name', $raw)->where('cis_row_id', '!=', (string) $g?->cis_row_id)->exists()) {
                    throw new DatasetImportException("Es gibt bereits eine Gruppe „{$raw}“.");
                }

                return ['label' => 'Gruppenname', 'old' => (string) $g?->name, 'new' => $raw, 'set' => ['name' => $raw]];

            case 'gr_description':
            case 'gr_color':
                $attr = $field === 'gr_description' ? 'description' : 'color';
                if ($raw === (string) ($g?->{$attr})) {
                    return null;
                }

                return ['label' => $this->fields()[$field]['label'], 'old' => (string) $g?->{$attr}, 'new' => $raw, 'set' => [$attr => $raw !== '' ? $raw : null]];

            case 'gr_members':
                $emails = $this->splitList($raw);
                $ids    = [];
                foreach ($emails as $email) {
                    $user = User::whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();
                    if (! $user) {
                        throw new DatasetImportException("Mitglied „{$email}“: kein Benutzer mit dieser E-Mail.");
                    }
                    $ids[] = $user->cis_row_id;
                }
                $current = $g ? $g->users->pluck('email')->all() : [];
                if ($this->sameSet($current, $emails)) {
                    return null;
                }

                return ['label' => 'Mitglieder', 'old' => implode('; ', $current), 'new' => implode('; ', $emails), 'after' => fn (Group $m) => $m->users()->sync($ids)];

            case 'gr_permissions':
                return $this->importPermissions($g, $raw);
        }

        return null;
    }
}
