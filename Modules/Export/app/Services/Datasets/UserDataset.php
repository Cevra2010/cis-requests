<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Systemdatensatz "Benutzer" inkl. Rollen- und Gruppenzuordnung. Neue Benutzer lassen sich nicht importieren (Passwort/Einladung). */
class UserDataset extends Dataset
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return 'Benutzer';
    }

    public function fields(): array
    {
        return [
            'us_id'          => ['label' => 'Benutzer-ID',               'origin' => 'users'],
            'us_firstname'   => ['label' => 'Vorname',                   'origin' => 'users', 'import' => true],
            'us_lastname'    => ['label' => 'Nachname',                  'origin' => 'users', 'import' => true],
            'us_name'        => ['label' => 'Vollständiger Name',        'origin' => 'users'],
            'us_email'       => ['label' => 'E-Mail',                    'origin' => 'users', 'import' => true],
            'us_phone'       => ['label' => 'Telefon',                   'origin' => 'users', 'import' => true, 'clearable' => true],
            'us_birthdate'   => ['label' => 'Geburtsdatum',              'origin' => 'users', 'import' => true, 'clearable' => true],
            'us_bio'         => ['label' => 'Beschreibung / Bio',        'origin' => 'users', 'import' => true, 'clearable' => true],
            'us_verified'    => ['label' => 'E-Mail bestätigt am',       'origin' => 'users'],
            'us_roles'       => ['label' => 'Rollen',                    'origin' => 'roles', 'import' => true, 'clearable' => true],
            'us_groups'      => ['label' => 'Gruppen',                   'origin' => 'groups', 'import' => true, 'clearable' => true],
            'us_direct_perm' => ['label' => 'Direkte Berechtigungen (Anzahl)', 'origin' => 'users'],
            'us_created'     => ['label' => 'Angelegt am',               'origin' => 'users'],
            'us_updated'     => ['label' => 'Zuletzt geändert am',       'origin' => 'users'],
        ];
    }

    protected function records(): iterable
    {
        return User::with(['roles', 'groups'])->orderBy('lastname')->orderBy('firstname')->get();
    }

    protected function exportValue(Model $u, string $field): string
    {
        return match ($field) {
            'us_id'          => $u->cis_row_id,
            'us_firstname'   => (string) $u->firstname,
            'us_lastname'    => (string) $u->lastname,
            'us_name'        => $u->name(),
            'us_email'       => (string) $u->email,
            'us_phone'       => (string) $u->phone,
            'us_birthdate'   => $this->fmtDate($u->birthdate),
            'us_bio'         => (string) $u->bio,
            'us_verified'    => $this->fmtDate($u->email_verified_at, true),
            'us_roles'       => $u->roles->pluck('name')->sort()->join('; '),
            'us_groups'      => $u->groups->pluck('name')->sort()->join('; '),
            'us_direct_perm' => (string) DB::table('user_permissions')->where('user_id', $u->cis_row_id)->count(),
            'us_created'     => $this->fmtDate($u->created_at, true),
            'us_updated'     => $this->fmtDate($u->updated_at, true),
            default          => '',
        };
    }

    protected function findRecord(string $key): ?Model
    {
        return User::with(['roles', 'groups'])->where('cis_row_id', $key)->first();
    }

    protected function recordLabel(?Model $record, \Closure $get): string
    {
        return $record ? $record->name() : 'unbekannt';
    }

    protected function importField(?Model $u, string $field, string $raw, \Closure $get): ?array
    {
        switch ($field) {
            case 'us_firstname':
            case 'us_lastname':
            case 'us_phone':
            case 'us_bio':
                $attr  = ['us_firstname' => 'firstname', 'us_lastname' => 'lastname', 'us_phone' => 'phone', 'us_bio' => 'bio'][$field];
                $label = $this->fields()[$field]['label'];
                if (in_array($attr, ['firstname', 'lastname'], true) && $raw === '') {
                    return null;
                }

                return $raw === (string) $u->{$attr} ? null : ['label' => $label, 'old' => (string) $u->{$attr}, 'new' => $raw, 'set' => [$attr => $raw !== '' ? $raw : null]];

            case 'us_email':
                if (mb_strtolower($raw) === mb_strtolower((string) $u->email)) {
                    return null;
                }
                if (! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                    throw new DatasetImportException("E-Mail „{$raw}“ ist ungültig.");
                }
                if (User::withTrashed()->where('email', $raw)->where('cis_row_id', '!=', $u->cis_row_id)->exists()) {
                    throw new DatasetImportException("E-Mail „{$raw}“ wird bereits von einem anderen Benutzer verwendet.");
                }

                return ['label' => 'E-Mail', 'old' => (string) $u->email, 'new' => $raw, 'set' => ['email' => $raw]];

            case 'us_birthdate':
                $new = $raw === '' ? null : $this->parseDate($raw, 'Geburtsdatum');
                $old = $u->birthdate;

                return ($new?->format('Y-m-d')) === ($old?->format('Y-m-d')) ? null : ['label' => 'Geburtsdatum', 'old' => $this->fmtDate($old), 'new' => $this->fmtDate($new), 'set' => ['birthdate' => $new?->format('Y-m-d')]];

            case 'us_roles':
                return $this->importRelation($u, $raw, 'Rollen', Role::class, 'roles');

            case 'us_groups':
                return $this->importRelation($u, $raw, 'Gruppen', Group::class, 'groups');
        }

        return null;
    }

    /** Ersetzt die Rollen-/Gruppenzuordnung (Namen, mit Semikolon getrennt) des Benutzers. */
    private function importRelation(User $u, string $raw, string $label, string $modelClass, string $relation): ?array
    {
        $names = $this->splitList($raw);
        $all   = $modelClass::all()->keyBy(fn ($m) => mb_strtolower($m->name));
        $ids   = [];
        foreach ($names as $name) {
            $model = $all[mb_strtolower($name)] ?? null;
            if (! $model) {
                throw new DatasetImportException("{$label}: „{$name}“ existiert nicht.");
            }
            $ids[] = $model->cis_row_id;
        }

        $current = $u->{$relation}->pluck('name')->all();
        if ($this->sameSet($current, $names)) {
            return null;
        }

        return [
            'label' => $label,
            'old'   => implode('; ', $current),
            'new'   => implode('; ', $names),
            'after' => fn (User $model) => $model->{$relation}()->sync($ids),
        ];
    }
}
