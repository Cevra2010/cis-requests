<?php

namespace Modules\Export\Services\Datasets;

/** Registry der Systemdatensätze des Import-/Export-Werkzeugs. */
class Datasets
{
    /** @return array<string, Dataset> alle Datensätze (auch aktuell nicht verfügbare) */
    public static function all(): array
    {
        static $all = null;

        return $all ??= collect([
            new ProductDataset(),
            new UserDataset(),
            new RoleDataset(),
            new GroupDataset(),
            new ProjectDataset(),
            new LagerortDataset(),
            new LagerStockDataset(),
        ])->keyBy(fn (Dataset $d) => $d->key())->all();
    }

    public static function find(string $key): ?Dataset
    {
        return self::all()[$key] ?? null;
    }

    /** @return array<string, Dataset> */
    public static function available(): array
    {
        return array_filter(self::all(), fn (Dataset $d) => $d->available());
    }
}
