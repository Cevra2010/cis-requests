<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Systemdatensatz "Produkte": Produkte inkl. Kategorie, fester Quelle, aktuellem Preis und Unterprodukten. */
class ProductDataset extends Dataset
{
    public function key(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return 'Produkte';
    }

    public function fields(): array
    {
        return [
            'pd_id'             => ['label' => 'Produkt-ID',                    'origin' => 'products'],
            'pd_name'           => ['label' => 'Produktname',                   'origin' => 'products', 'import' => true],
            'pd_is_set'         => ['label' => 'Set-Produkt',                   'origin' => 'products', 'import' => true],
            'pd_in_estimate'    => ['label' => 'In Kostenschätzung',            'origin' => 'products', 'import' => true],
            'pd_tender_relevant' => ['label' => 'Ausschreibungsrelevant',       'origin' => 'products'],
            'pd_description'    => ['label' => 'Beschreibungstext (Standard)',  'origin' => 'products'],
            'pd_children'       => ['label' => 'Unterprodukte',                 'origin' => 'products'],
            'pd_parents'        => ['label' => 'Elternprodukte',                'origin' => 'products'],
            'pd_category'       => ['label' => 'Kategorie',                     'origin' => 'categories', 'import' => true],
            'pd_source'         => ['label' => 'Feste Quelle',                  'origin' => 'sources', 'import' => true, 'clearable' => true],
            'pd_price'          => ['label' => 'Aktueller Preis',               'origin' => 'prices', 'import' => true],
            'pd_price_source'   => ['label' => 'Quelle des aktuellen Preises',  'origin' => 'prices', 'import' => true],
            'pd_price_date'     => ['label' => 'Preis erfasst am',              'origin' => 'prices'],
            'pd_price_count'    => ['label' => 'Anzahl Preiseinträge',          'origin' => 'prices'],
            'pd_created'        => ['label' => 'Angelegt am',                   'origin' => 'products'],
            'pd_updated'        => ['label' => 'Zuletzt geändert am',           'origin' => 'products'],
        ];
    }

    public function canCreate(): bool
    {
        return true;
    }

    protected function requiredForCreate(): array
    {
        return ['pd_name'];
    }

    protected function records(): iterable
    {
        return Product::with(['category', 'source', 'childs', 'parents'])->orderBy('name')->get();
    }

    protected function exportValue(Model $p, string $field): string
    {
        $price = in_array($field, ['pd_price', 'pd_price_source', 'pd_price_date'], true) ? $p->price() : null;

        return match ($field) {
            'pd_id'              => $p->cis_row_id,
            'pd_name'            => $p->name,
            'pd_is_set'          => $p->isSet() ? 'Ja' : 'Nein',
            'pd_in_estimate'     => $p->include_in_estimate ? 'Ja' : 'Nein',
            'pd_tender_relevant' => $p->isTenderRelevant() ? 'Ja' : 'Nein',
            'pd_description'     => (string) ($p->description()?->text ?? ''),
            'pd_children'        => $p->childs->pluck('name')->join('; '),
            'pd_parents'         => $p->parents->pluck('name')->join('; '),
            'pd_category'        => $p->category?->name ?? '',
            'pd_source'          => $p->source?->name ?? '',
            'pd_price'           => $price ? number_format($price->amount, 2, ',', '') : '',
            'pd_price_source'    => $price?->source?->name ?? '',
            'pd_price_date'      => $this->fmtDate($price?->created_at, true),
            'pd_price_count'     => (string) $p->prices()->count(),
            'pd_created'         => $this->fmtDate($p->created_at, true),
            'pd_updated'         => $this->fmtDate($p->updated_at, true),
            default              => '',
        };
    }

    protected function findRecord(string $key): ?Model
    {
        return Product::with(['category', 'source'])->where('cis_row_id', $key)->first();
    }

    protected function newRecord(): Model
    {
        return new Product(['include_in_estimate' => true]);
    }

    protected function importField(?Model $p, string $field, string $raw, \Closure $get): ?array
    {
        switch ($field) {
            case 'pd_name':
                return $raw === ($p?->name) ? null : ['label' => 'Produktname', 'old' => (string) $p?->name, 'new' => $raw, 'set' => ['name' => $raw]];

            case 'pd_is_set':
                $new = $this->parseBool($raw, 'Set-Produkt');

                return $new === (bool) ($p?->is_set) ? null : ['label' => 'Set-Produkt', 'old' => $p?->is_set ? 'Ja' : 'Nein', 'new' => $new ? 'Ja' : 'Nein', 'set' => ['is_set' => $new]];

            case 'pd_in_estimate':
                $new = $this->parseBool($raw, 'In Kostenschätzung');
                $old = $p ? (bool) $p->include_in_estimate : true;

                return $new === $old ? null : ['label' => 'In Kostenschätzung', 'old' => $old ? 'Ja' : 'Nein', 'new' => $new ? 'Ja' : 'Nein', 'set' => ['include_in_estimate' => $new]];

            case 'pd_category':
                $category = Category::ofType('product.category')->get()->first(fn ($c) => mb_strtolower($c->name) === mb_strtolower($raw));
                if (! $category) {
                    throw new DatasetImportException("Kategorie „{$raw}“ existiert nicht.");
                }

                return $category->id === $p?->category_id ? null : ['label' => 'Kategorie', 'old' => (string) $p?->category?->name, 'new' => $category->name, 'set' => ['category_id' => $category->id]];

            case 'pd_source':
                $source = $raw === '' ? null : ProductSource::all()->first(fn ($s) => mb_strtolower($s->name) === mb_strtolower($raw));
                if ($raw !== '' && ! $source) {
                    throw new DatasetImportException("Feste Quelle „{$raw}“ existiert nicht.");
                }

                return $source?->cis_row_id === $p?->cis_row_id_source ? null : ['label' => 'Feste Quelle', 'old' => (string) $p?->source?->name, 'new' => (string) $source?->name, 'set' => ['cis_row_id_source' => $source?->cis_row_id]];

            case 'pd_price':
                $amount  = $this->parseDecimal($raw, 'Preis');
                $current = $p?->price();
                if ($current && round((float) $current->amount, 2) === round($amount, 2)) {
                    return null;
                }

                $sourceName  = $get('pd_price_source');
                $priceSource = ($sourceName !== null && $sourceName !== '')
                    ? ProductSource::all()->first(fn ($s) => mb_strtolower($s->name) === mb_strtolower($sourceName))
                    : $current?->source;
                if (! $priceSource) {
                    throw new DatasetImportException(($sourceName ?? '') !== ''
                        ? "Preisquelle „{$sourceName}“ existiert nicht."
                        : 'Preisänderung braucht eine Preisquelle (Spalte „Quelle des aktuellen Preises“).');
                }

                return [
                    'label' => 'Preis',
                    'old'   => $current ? number_format($current->amount, 2, ',', '.') . ' €' : '',
                    'new'   => number_format($amount, 2, ',', '.') . ' € (' . $priceSource->name . ')',
                    'after' => function (Product $product) use ($amount, $priceSource) {
                        $price = new Price();
                        $price->cis_row_id_product = $product->cis_row_id;
                        $price->cis_row_id_source  = $priceSource->cis_row_id;
                        $price->amount             = $amount;
                        $price->save();
                    },
                ];
        }

        return null; // pd_price_source wird zusammen mit pd_price ausgewertet
    }
}
