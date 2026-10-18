<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Systemdatensatz "Rollen" inkl. Mitgliedern und global erlaubten Berechtigungen. */
class RoleDataset extends Dataset
{
    use SyncsPermissions;

    public function key(): string
    {
        return 'roles';
    }

    public function label(): string
    {
        return 'Rollen';
    }

    protected function permissionTable(): array
    {
        return ['role_permissions', 'role_id'];
    }

    public function fields(): array
    {
        return [
            'ro_id'            => ['label' => 'Rollen-ID',                          'origin' => 'roles'],
            'ro_name'          => ['label' => 'Rollenname',                         'origin' => 'roles', 'import' => true],
            'ro_description'   => ['label' => 'Beschreibung',                       'origin' => 'roles', 'import' => true, 'clearable' => true],
            'ro_color'         => ['label' => 'Farbe',                              'origin' => 'roles', 'import' => true, 'clearable' => true],
            'ro_users'         => ['label' => 'Mitglieder (E-Mail)',                'origin' => 'users', 'import' => true, 'clearable' => true],
            'ro_user_count'    => ['label' => 'Anzahl Mitglieder',                  'origin' => 'roles'],
            'ro_permissions'   => ['label' => 'Berechtigungen (global)',            'origin' => 'roles', 'import' => true, 'clearable' => true],
            'ro_perm_count'    => ['label' => 'Anzahl Berechtigungen (global)',     'origin' => 'roles'],
            'ro_perm_project'  => ['label' => 'Projektbezogene Berechtigungen (Anzahl)', 'origin' => 'roles'],
            'ro_perm_denied'   => ['label' => 'Verweigerte Berechtigungen (Anzahl)', 'origin' => 'roles'],
            'ro_created'       => ['label' => 'Angelegt am',                        'origin' => 'roles'],
            'ro_updated'       => ['label' => 'Zuletzt geändert am',                'origin' => 'roles'],
        ];
    }

    public function canCreate(): bool
    {
        return true;
    }

    protected function requiredForCreate(): array
    {
        return ['ro_name'];
    }

    protected function records(): iterable
    {
        return Role::with('users')->orderBy('name')->get();
    }

    protected function exportValue(Model $r, string $field): string
    {
        return match ($field) {
            'ro_id'           => $r->cis_row_id,
            'ro_name'         => $r->name,
            'ro_description'  => (string) $r->description,
            'ro_color'        => (string) $r->color,
            'ro_users'        => $r->users->pluck('email')->sort()->join('; '),
            'ro_user_count'   => (string) $r->users->count(),
            'ro_permissions'  => implode('; ', $this->globalPermissionSlugs($r)),
            'ro_perm_count'   => (string) $this->permissionCount($r, false),
            'ro_perm_project' => (string) $this->permissionCount($r, true),
            'ro_perm_denied'  => (string) ($this->permissionCount($r, false, false) + $this->permissionCount($r, true, false)),
            'ro_created'      => $this->fmtDate($r->created_at, true),
            'ro_updated'      => $this->fmtDate($r->updated_at, true),
            default           => '',
        };
    }

    protected function findRecord(string $key): ?Model
    {
        return Role::with('users')->where('cis_row_id', $key)->first();
    }

    protected function newRecord(): Model
    {
        return new Role();
    }

    protected function recordLabel(?Model $record, \Closure $get): string
    {
        return $record?->name ?? ($get('ro_name') ?: 'neue Rolle');
    }

    protected function importField(?Model $r, string $field, string $raw, \Closure $get): ?array
    {
        switch ($field) {
            case 'ro_name':
                if ($raw === ($r?->name)) {
                    return null;
                }
                if (Role::withTrashed()->where('name', $raw)->where('cis_row_id', '!=', (string) $r?->cis_row_id)->exists()) {
                    throw new DatasetImportException("Es gibt bereits eine Rolle „{$raw}“.");
                }

                return ['label' => 'Rollenname', 'old' => (string) $r?->name, 'new' => $raw, 'set' => ['name' => $raw]];

            case 'ro_description':
            case 'ro_color':
                $attr = $field === 'ro_description' ? 'description' : 'color';
                if ($raw === (string) ($r?->{$attr})) {
                    return null;
                }

                return ['label' => $this->fields()[$field]['label'], 'old' => (string) $r?->{$attr}, 'new' => $raw, 'set' => [$attr => $raw !== '' ? $raw : null]];

            case 'ro_users':
                $emails = $this->splitList($raw);
                $ids    = [];
                foreach ($emails as $email) {
                    $user = User::whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();
                    if (! $user) {
                        throw new DatasetImportException("Mitglied „{$email}“: kein Benutzer mit dieser E-Mail.");
                    }
                    $ids[] = $user->cis_row_id;
                }
                $current = $r ? $r->users->pluck('email')->all() : [];
                if ($this->sameSet($current, $emails)) {
                    return null;
                }

                return ['label' => 'Mitglieder', 'old' => implode('; ', $current), 'new' => implode('; ', $emails), 'after' => fn (Role $m) => $m->users()->sync($ids)];

            case 'ro_permissions':
                return $this->importPermissions($r, $raw);
        }

        return null;
    }
}
