<?php

namespace App\Http\Livewire\Product;

use App\Http\Livewire\Concerns\HasFilterableTable;
use App\Models\Product;
use CisFoundation\CisCategoryManager\CisCategoryManager;
use Livewire\Component;

class ProductTable extends Component
{
    use HasFilterableTable;

    public function tableKey(): string
    {
        return 'products';
    }

    public function baseQuery()
    {
        return Product::query()->with(['prices.source', 'childs.category', 'category']);
    }

    public function columns(): array
    {
        return [
            [
                'key' => 'name', 'label' => 'Name',
                'sortable' => true, 'filterable' => true,
                'column' => 'name', 'value' => fn ($p) => $p->name,
            ],
            [
                'key' => 'category', 'label' => 'Kategorie',
                'sortable' => true, 'filterable' => true,
                'column' => 'category_id', 'value' => fn ($p) => $p->category_id,
                'sortValue' => fn ($p) => $p->category?->name ?? '',
                'optionsSource' => fn () => CisCategoryManager::optionsForType('product.category'),
            ],
            [
                'key' => 'price', 'label' => 'Produktpreis',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($p) => $p->isSet() ? null : $p->price()?->amount,
                'format' => fn ($v) => number_format((float) $v, 2, ',', '.') . ' €',
            ],
            [
                'key' => 'group_price', 'label' => 'Gesamtpreis',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($p) => $p->isSet()
                    ? null
                    : ($p->hasChild() ? $p->getGroupPrice() : $p->price()?->amount),
                'format' => fn ($v) => number_format((float) $v, 2, ',', '.') . ' €',
            ],
            [
                'key' => 'source', 'label' => 'Lieferant',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($p) => $p->price()?->source?->name,
            ],
            [
                'key' => 'created_at', 'label' => 'Erstellt',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($p) => optional($p->created_at)->format('d.m.Y'),
                'sortValue' => fn ($p) => $p->created_at,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.product.product-table', [
            'rows' => $this->paginatedRows(),
        ]);
    }
}
