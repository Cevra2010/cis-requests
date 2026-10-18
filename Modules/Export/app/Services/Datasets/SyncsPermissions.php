<?php

namespace Modules\Export\Services\Datasets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Gemeinsame Berechtigungs-Ein-/Ausgabe für Rollen und Gruppen (Tabellen role_permissions / group_permissions). */
trait SyncsPermissions
{
    /** @return array{0: string, 1: string} [Tabelle, Fremdschlüsselspalte] */
    abstract protected function permissionTable(): array;

    /** @return string[] Slugs der global (ohne Projektbezug) erlaubten Berechtigungen */
    protected function globalPermissionSlugs(Model $record): array
    {
        [$table, $fk] = $this->permissionTable();

        return DB::table($table)->where($fk, $record->cis_row_id)->whereNull('project_id')->where('granted', true)
            ->orderBy('permission_slug')->pluck('permission_slug')->all();
    }

    protected function permissionCount(Model $record, bool $projectBound, bool $granted = true): int
    {
        [$table, $fk] = $this->permissionTable();

        return DB::table($table)->where($fk, $record->cis_row_id)->where('granted', $granted)
            ->when($projectBound, fn ($q) => $q->whereNotNull('project_id'), fn ($q) => $q->whereNull('project_id'))->count();
    }

    /** Importzelle "Berechtigungen (global)": ersetzt die globalen Erlaubnisse durch die angegebenen Slugs. */
    protected function importPermissions(?Model $record, string $raw): ?array
    {
        $slugs = $this->splitList($raw);
        $known = DB::table('permissions')->pluck('slug')->all();
        $unknown = array_values(array_diff($slugs, $known));
        if ($unknown) {
            throw new DatasetImportException('Unbekannte Berechtigung(en): ' . implode(', ', $unknown) . '.');
        }

        $current = $record ? $this->globalPermissionSlugs($record) : [];
        if ($this->sameSet($current, $slugs)) {
            return null;
        }

        [$table, $fk] = $this->permissionTable();

        return [
            'label' => 'Berechtigungen (global)',
            'old'   => implode('; ', $current),
            'new'   => implode('; ', $slugs),
            'after' => function (Model $model) use ($table, $fk, $slugs) {
                DB::table($table)->where($fk, $model->cis_row_id)->whereNull('project_id')->where('granted', true)
                    ->whereNotIn('permission_slug', $slugs)->delete();

                $existing = DB::table($table)->where($fk, $model->cis_row_id)->whereNull('project_id')->pluck('granted', 'permission_slug');
                foreach ($slugs as $slug) {
                    if (! $existing->has($slug)) {
                        DB::table($table)->insert([$fk => $model->cis_row_id, 'permission_slug' => $slug, 'project_id' => null, 'granted' => true]);
                    } elseif (! $existing[$slug]) {
                        DB::table($table)->where($fk, $model->cis_row_id)->whereNull('project_id')->where('permission_slug', $slug)->update(['granted' => true]);
                    }
                }
            },
        ];
    }
}
