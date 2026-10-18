@php $actionType = \App\Services\Workflow\Actions\WorkflowActionTypes::find($action->type); @endphp
<div wire:key="action-{{ $action->cis_row_id }}" data-id="{{ $action->cis_row_id }}"
     class="flex items-center gap-1.5 px-2 py-1 rounded-md border {{ ($actionType?->editable() ?? true) ? 'bg-gray-50 border-gray-100' : 'bg-indigo-50 border-indigo-100' }} {{ ! $action->is_active ? 'opacity-50' : '' }}">
    <span class="js-action-drag-handle w-3 h-3 flex items-center justify-center text-gray-300 hover:text-gray-500 cursor-grab shrink-0">
        <i class="fa fa-grip-vertical text-[9px]"></i>
    </span>
    <i class="fa fa-{{ $actionType?->icon() ?? 'bolt' }} text-gray-400 text-[10px] shrink-0"></i>
    @if($actionType?->editable() ?? true)
        <button type="button" wire:click.stop="editAction('{{ $action->cis_row_id }}')"
                class="flex-1 min-w-0 text-left text-[11px] text-gray-600 truncate hover:underline">
            {{ $actionType?->summary($action->config ?? []) ?? $action->type }}
        </button>
    @else
        <span class="flex-1 min-w-0 text-[11px] text-indigo-700 font-medium truncate" title="{{ $actionType->summary([]) }}">{{ $actionType->summary([]) }}</span>
    @endif
    <button type="button" wire:click.stop="toggleActionActive('{{ $action->cis_row_id }}')"
            title="{{ $action->is_active ? 'Deaktivieren' : 'Aktivieren' }}"
            class="text-[10px] text-gray-400 hover:bg-gray-100 rounded px-1 shrink-0">
        <i class="fa {{ $action->is_active ? 'fa-toggle-on text-primary-500' : 'fa-toggle-off' }}"></i>
    </button>
    <button type="button" wire:click.stop="deleteAction('{{ $action->cis_row_id }}')" class="text-gray-300 hover:text-red-500 shrink-0">
        <i class="fa fa-trash text-[9px]"></i>
    </button>
</div>
