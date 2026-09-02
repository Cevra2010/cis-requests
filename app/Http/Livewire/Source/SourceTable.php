<?php

namespace App\Http\Livewire\Source;

use App\Http\Livewire\Concerns\HasFilterableTable;
use App\Models\ProductSource;
use Livewire\Component;

class SourceTable extends Component
{
    use HasFilterableTable;

    public function tableKey(): string
    {
        return 'sources';
    }

    public function baseQuery()
    {
        return ProductSource::query();
    }

    public function columns(): array
    {
        return [
            [
                'key' => 'name', 'label' => 'Name',
                'sortable' => true, 'filterable' => true,
                'column' => 'name', 'value' => fn ($s) => $s->name,
            ],
            [
                'key' => 'contact_name', 'label' => 'Ansprechpartner',
                'sortable' => true, 'filterable' => true,
                'column' => 'contact_name', 'value' => fn ($s) => $s->contact_name,
            ],
            [
                'key' => 'contact_email', 'label' => 'E-Mail',
                'sortable' => true, 'filterable' => true,
                'column' => 'contact_email', 'value' => fn ($s) => $s->contact_email,
            ],
            [
                'key' => 'contact_phone', 'label' => 'Telefon',
                'sortable' => true, 'filterable' => true,
                'column' => 'contact_phone', 'value' => fn ($s) => $s->contact_phone,
            ],
            [
                'key' => 'url', 'label' => 'Website',
                'sortable' => true, 'filterable' => true,
                'column' => 'url', 'value' => fn ($s) => $s->url,
                'format' => fn ($v) => parse_url((string) $v, PHP_URL_HOST) ?? $v,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.source.source-table', [
            'rows' => $this->paginatedRows(),
        ]);
    }
}
