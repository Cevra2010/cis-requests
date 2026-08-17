<?php

namespace CisFoundation\CisTableBuilder\Component;

use CisFoundation\CisTableBuilder\CisTableBuilder;
use Illuminate\View\Component;

class CisTableComponent extends Component
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function render()
    {
        $def     = CisTableBuilder::get($this->name);
        $columns = $def->getColumns();
        $actions = $def->getActions();
        $data    = $def->getData();

        return view('cis-table-builder::livewire.table', compact('def', 'columns', 'actions', 'data'));
    }
}
