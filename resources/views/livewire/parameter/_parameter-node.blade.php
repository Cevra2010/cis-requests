@php
$hasChildren = $node->children->isNotEmpty();
@endphp
<div x-data="{ open: true }" wire:key="param-node-{{ $node->cis_row_id }}" class="{{ $depth > 0 ? 'ml-6 border-l border-gray-100 pl-3' : '' }}">
    <div class="group flex items-start gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-50">
        <button type="button" @click="open = !open"
                class="w-4 h-4 flex items-center justify-center text-gray-400 shrink-0 mt-0.5 {{ ! $hasChildren ? 'invisible' : '' }}">
            <i class="fa fa-chevron-right text-[10px] transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-gray-800">{{ $node->name }}</span>
                @if($node->category)
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ $node->category->name }}</span>
                @endif
                @if($hasChildren)
                    <span class="text-[10px] text-gray-300">{{ $node->children->count() }} Unter-Parameter</span>
                @endif
            </div>
            @if($node->description)
                <p class="text-xs text-gray-400 mt-0.5">{{ $node->description }}</p>
            @endif
        </div>

        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            @can('parameter.create')
            <button type="button" wire:click="openCreate('{{ $node->cis_row_id }}')" title="Unter-Parameter anlegen"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-primary-600">
                <i class="fa fa-plus text-xs"></i>
            </button>
            @endcan
            @can('parameter.edit')
            <button type="button" wire:click="openEdit('{{ $node->cis_row_id }}')" title="Bearbeiten"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-gray-700">
                <i class="fa fa-pencil text-xs"></i>
            </button>
            @endcan
            @can('parameter.delete')
            <button type="button" wire:click="confirmDelete('{{ $node->cis_row_id }}')" title="Löschen"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-red-500">
                <i class="fa fa-trash text-xs"></i>
            </button>
            @endcan
        </div>
    </div>

    @if($hasChildren)
    <div x-show="open" x-cloak>
        @foreach($node->children as $child)
            @include('livewire.parameter._parameter-node', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
    @endif
</div>
