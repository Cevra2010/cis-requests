<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use Illuminate\Support\Facades\DB;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Services\TenderExporter;

/**
 * Systemdatensatz "Produkte": alle Produkte inkl. Kategorie, fester Quelle und
 * aktuellem Preis. Export und Import laufen über dieselbe Vorlage; der Import
 * ordnet Zeilen über den Import-Key (= Produkt-ID, Spalte "_ImportKey") zu und
 * liefert zuerst einen Plan (plan()), der erst durch apply() geschrieben wird.
 */
class ProductDataset
{
    public const KEY   = 'products';
    public const LABEL = 'Produkte';

    /** Felder dieses Datensatzes; 'origin' gruppiert sie in der Feldauswahl (siehe ExportFieldRegistry::ORIGINS). */
    public const FIELDS = [
        'pd_name'         => ['label' => 'Produktname',                'origin' => 'products'],
        'pd_is_set'       => ['label' => 'Set-Produkt',                'origin' => 'products'],
        'pd_category'     => ['label' => 'Kategorie',                  'origin' => 'categories'],
        'pd_source'       => ['label' => 'Feste Quelle',               'origin' => 'sources'],
        'pd_price'        => ['label' => 'Aktueller Preis',            'origin' => 'prices'],
        'pd_price_source' => ['label' => 'Quelle des aktuellen Preises', 'origin' => 'prices'],
    ];

    /** @return array{headers: array<int, string>, rows: array<int, array<int, string>>, import_key_index: ?int} */
    public function build(ExportTemplate $template): array
    {
        $columns    = $template->columns;
        $headers    = $columns->pluck('label')->all();
        $withKey    = (bool) $template->include_import_key;
        $keyIndex   = $withKey ? count($headers) : null;
        if ($withKey) {
            $headers[] = TenderExporter::IMPORT_KEY_HEADER;
        }

        $rows = [];
        Product::with(['category', 'source'])->orderBy('name')->get()->each(function (Product $product) use ($columns, $withKey, &$rows) {
            $price = $product->price();
            $row   = [];
            foreach ($columns as $column) {
                $row[] = $column->isFreeField() ? ($column->static_value ?? '') : match ($column->field_key) {
                    'pd_name'         => $product->name,
                    'pd_is_set'       => $product->isSet() ? 'Ja' : 'Nein',
                    'pd_category'     => $product->category?->name ?? '',
                    'pd_source'       => $product->source?->name ?? '',
                    'pd_price'        => $price ? number_format($price->amount, 2, ',', '') : '',
                    'pd_price_source' => $price?->source?->name ?? '',
                    default           => '',
                };
            }
            if ($withKey) {
                $row[] = $product->cis_row_id;
            }
            $rows[] = $row;
        });

        return ['headers' => $headers, 'rows' => $rows, 'import_key_index' => $keyIndex];
    }

    /**
     * Prüft die eingelesenen Zeilen (ohne Kopfzeile) gegen die Datenbank, ohne zu schreiben.
     *
     * @param array<int, array<int, mixed>> $sheetRows
     * @return array<int, array{line: int, action: string, label: string, changes: array<string, array{0: string, 1: string}>, error: ?string, data: array<string, mixed>}>
     */
    public function plan(ExportTemplate $template, array $sheetRows, bool $allowCreate): array
    {
        $columns  = $template->columns->values();
        $keyIndex = $columns->count();

        $fieldIndex = [];
        foreach ($columns as $i => $column) {
            if (! $column->isFreeField() && isset(self::FIELDS[$column->field_key])) {
                $fieldIndex[$column->field_key] = $i;
            }
        }

        $categories = Category::ofType('product.category')->get()->keyBy(fn ($c) => mb_strtolower($c->name));
        $sources    = ProductSource::all()->keyBy(fn ($s) => mb_strtolower($s->name));

        $plan = [];
        foreach ($sheetRows as $n => $row) {
            $line = $n + 2; // Excel-Zeile (Kopfzeile = 1)
            $key  = trim((string) ($row[$keyIndex] ?? ''));
            $get  = fn (string $field) => isset($fieldIndex[$field]) ? trim((string) ($row[$fieldIndex[$field]] ?? '')) : null;

            if (collect($row)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                continue;
            }

            $product = $key !== '' ? Product::with(['category', 'source'])->where('cis_row_id', $key)->first() : null;
            $entry   = ['line' => $line, 'action' => 'unchanged', 'label' => $product?->name ?? ($get('pd_name') ?: '(ohne Namen)'), 'changes' => [], 'error' => null, 'data' => []];

            if ($key !== '' && ! $product) {
                $plan[] = [...$entry, 'action' => 'error', 'error' => 'Import-Key gehört zu keinem Produkt.'];
                continue;
            }
            if ($key === '') {
                if (! $allowCreate) {
                    $plan[] = [...$entry, 'action' => 'skipped', 'error' => 'Kein Import-Key – Anlegen neuer Produkte ist nicht aktiviert.'];
                    continue;
                }
                if (($get('pd_name') ?? '') === '') {
                    $plan[] = [...$entry, 'action' => 'error', 'error' => 'Neues Produkt braucht einen Produktnamen.'];
                    continue;
                }
                $entry['action'] = 'create';
            }

            $data    = [];
            $changes = [];
            $error   = null;

            if (($v = $get('pd_name')) !== null && $v !== '' && $v !== ($product?->name ?? null)) {
                $data['name'] = $v;
                $changes['Produktname'] = [$product?->name ?? '', $v];
            }
            if (($v = $get('pd_is_set')) !== null && $v !== '') {
                $bool = match (mb_strtolower($v)) { 'ja', 'yes', '1', 'true' => true, 'nein', 'no', '0', 'false' => false, default => null };
                if ($bool === null) {
                    $error = "Set-Produkt: „{$v}“ ist weder Ja noch Nein.";
                } elseif ($bool !== (bool) ($product?->is_set ?? false)) {
                    $data['is_set'] = $bool;
                    $changes['Set-Produkt'] = [($product?->is_set ?? false) ? 'Ja' : 'Nein', $bool ? 'Ja' : 'Nein'];
                }
            }
            if (($v = $get('pd_category')) !== null && $v !== '') {
                $category = $categories[mb_strtolower($v)] ?? null;
                if (! $category) {
                    $error = "Kategorie „{$v}“ existiert nicht.";
                } elseif ($category->id !== $product?->category_id) {
                    $data['category_id'] = $category->id;
                    $changes['Kategorie'] = [$product?->category?->name ?? '', $category->name];
                }
            }
            if (($v = $get('pd_source')) !== null) {
                $source = $v === '' ? null : ($sources[mb_strtolower($v)] ?? false);
                if ($source === false) {
                    $error = "Feste Quelle „{$v}“ existiert nicht.";
                } elseif (($source?->cis_row_id) !== ($product?->cis_row_id_source)) {
                    $data['cis_row_id_source'] = $source?->cis_row_id;
                    $changes['Feste Quelle'] = [$product?->source?->name ?? '', $source?->name ?? ''];
                }
            }
            if (($v = $get('pd_price')) !== null && $v !== '') {
                $amount = self::parseAmount($v);
                if ($amount === null) {
                    $error = "Preis „{$v}“ ist keine Zahl.";
                } else {
                    $current = $product?->price();
                    if (! $current || round((float) $current->amount, 2) !== round($amount, 2)) {
                        $sourceName = $get('pd_price_source');
                        $priceSource = ($sourceName !== null && $sourceName !== '')
                            ? ($sources[mb_strtolower($sourceName)] ?? false)
                            : ($current?->source ?? null);
                        if (! $priceSource) {
                            $error = ($sourceName ?? '') !== ''
                                ? "Preisquelle „{$sourceName}“ existiert nicht."
                                : 'Preisänderung braucht eine Preisquelle (Spalte „Quelle des aktuellen Preises“).';
                        } else {
                            $data['price']        = $amount;
                            $data['price_source'] = $priceSource->cis_row_id;
                            $changes['Preis'] = [$current ? number_format($current->amount, 2, ',', '.') . ' €' : '', number_format($amount, 2, ',', '.') . ' € (' . $priceSource->name . ')'];
                        }
                    }
                }
            }

            $entry['data']    = $data;
            $entry['changes'] = $changes;
            if ($error) {
                $entry['action'] = 'error';
                $entry['error']  = $error;
            } elseif ($entry['action'] !== 'create') {
                $entry['action'] = empty($changes) ? 'unchanged' : 'update';
            }
            if ($product) {
                $entry['data']['_id'] = $product->cis_row_id;
            }
            $plan[] = $entry;
        }

        return $plan;
    }

    /** Schreibt alle fehlerfreien Einträge des Plans (create/update) in einer Transaktion. @return array{created: int, updated: int} */
    public function apply(array $plan): array
    {
        $created = $updated = 0;

        DB::transaction(function () use ($plan, &$created, &$updated) {
            foreach ($plan as $entry) {
                if (! in_array($entry['action'], ['create', 'update'], true)) {
                    continue;
                }
                $data    = $entry['data'];
                $product = isset($data['_id']) ? Product::where('cis_row_id', $data['_id'])->first() : new Product(['include_in_estimate' => true]);

                $attrs = array_intersect_key($data, array_flip(['name', 'is_set', 'category_id', 'cis_row_id_source']));
                $product->fill($attrs);
                $product->save();

                if (isset($data['price'])) {
                    $price = new Price();
                    $price->cis_row_id_product = $product->cis_row_id;
                    $price->cis_row_id_source  = $data['price_source'];
                    $price->amount             = $data['price'];
                    $price->save();
                }

                $entry['action'] === 'create' ? $created++ : $updated++;
            }
        });

        return ['created' => $created, 'updated' => $updated];
    }

    /** "1.234,50", "12,5" und "12.50" → float; sonst null. */
    public static function parseAmount(string $raw): ?float
    {
        $s = trim(str_replace(['€', ' '], '', $raw));
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }
}
