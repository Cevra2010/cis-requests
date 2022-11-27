<div>
    @if($status)
        <a href="#setToggle" wire:click='setInactive("{{ $role->cis_row_id }}","{{ $area->cis_row_id }}")'>
            <i class="text-green-600 fa fa-toggle-on"></i>
        </a>
    @else
    <a href="#setToggle" wire:click='setActive("{{ $role->cis_row_id }}","{{ $area->cis_row_id }}")'>
            <i class="text-red-600 fa fa-toggle-off"></i>
        </a>
    @endif
</div>
