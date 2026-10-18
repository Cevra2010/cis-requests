<?php

namespace Modules\Export\Services\Datasets;

use Illuminate\Database\Eloquent\Model;
use Modules\Lager\Models\LagerStock;
use Nwidart\Modules\Facades\Module;

/**
 * Systemdatensatz "Lagerbestand" (Modul Lager): Bestand je Produkt/Lagerort/Projekt. Nur Export –
 * Bestände werden ausschließlich über den LagerStockService verändert (Buchungen), nie direkt.
 */
class LagerStockDataset extends Dataset
{
    public function key(): string
    {
        return 'lager_stock';
    }

    public function label(): string
    {
        return 'Lagerbestand';
    }

    public function available(): bool
    {
        return Module::find('Lager')?->isEnabled() ?? false;
    }

    public function fields(): array
    {
        return [
            'ls_product'   => ['label' => 'Produkt',               'origin' => 'products'],
            'ls_category'  => ['label' => 'Produktkategorie',      'origin' => 'categories'],
            'ls_code'      => ['label' => 'Lagerort-ID (Code)',    'origin' => 'lager'],
            'ls_location'  => ['label' => 'Lagerort',              'origin' => 'lager'],
            'ls_path'      => ['label' => 'Lagerort-Pfad',         'origin' => 'lager'],
            'ls_assigned'  => ['label' => 'Zuordnung',             'origin' => 'lager'],
            'ls_project'   => ['label' => 'Projekt',               'origin' => 'projects'],
            'ls_quantity'  => ['label' => 'Menge (Stück)',         'origin' => 'lager'],
            'ls_updated'   => ['label' => 'Zuletzt geändert am',   'origin' => 'lager'],
        ];
    }

    protected function records(): iterable
    {
        return LagerStock::with(['product.category', 'lagerort.type', 'lagerort.parent', 'project'])
            ->where('quantity', '>', 0)->get()
            ->sortBy(fn (LagerStock $s) => mb_strtolower(($s->product?->name ?? '') . '|' . ($s->lagerort?->name ?? '')))->values();
    }

    protected function exportValue(Model $s, string $field): string
    {
        return match ($field) {
            'ls_product'  => (string) $s->product?->name,
            'ls_category' => (string) $s->product?->category?->name,
            'ls_code'     => (string) $s->lagerort?->code(),
            'ls_location' => (string) $s->lagerort?->name,
            'ls_path'     => (string) $s->lagerort?->path(),
            'ls_assigned' => $s->isUnassigned() ? 'Frei verfügbar' : 'Projekt zugeordnet',
            'ls_project'  => (string) $s->project?->name,
            'ls_quantity' => (string) $s->quantity,
            'ls_updated'  => $this->fmtDate($s->updated_at, true),
            default       => '',
        };
    }
}
