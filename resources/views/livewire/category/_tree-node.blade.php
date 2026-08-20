@php
$hasChildren = $node->children->isNotEmpty();
@endphp
<div x-data="{ open: true }" wire:key="cat-node-{{ $node->id }}" class="{{ $depth > 0 ? 'ml-6 border-l border-gray-100 pl-3' : '' }}">
    <div class="group flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-50">
        <button type="button" @click="open = !open"
                class="w-4 h-4 flex items-center justify-center text-gray-400 shrink-0 {{ ! $hasChildren ? 'invisible' : '' }}">
            <i class="fa fa-chevron-right text-[10px] transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>

        @if($node->color)
            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $node->color }}"></span>
        @endif

        <span class="text-sm font-medium text-gray-800">{{ $node->name }}</span>

        @if($node->description)
            <span class="text-xs text-gray-400 truncate">— {{ $node->description }}</span>
        @endif

        @if($hasChildren)
            <span class="text-[10px] text-gray-300 shrink-0">{{ $node->children->count() }}</span>
        @endif

        <div class="ml-auto flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            @can('category.create')
            <button type="button" wire:click="openCreate({{ $node->id }})" title="Unterkategorie anlegen"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-primary-600">
                <i class="fa fa-plus text-xs"></i>
            </button>
            @endcan
            @can('category.edit')
            <button type="button" wire:click="openEdit({{ $node->id }})" title="Bearbeiten"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-gray-700">
                <i class="fa fa-pencil text-xs"></i>
            </button>
            @endcan
            @can('category.delete')
            <button type="button" wire:click="confirmDelete({{ $node->id }})" title="Löschen"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-red-500">
                <i class="fa fa-trash text-xs"></i>
            </button>
            @endcan
        </div>
    </div>

    @if($hasChildren)
    <div x-show="open" x-cloak>
        @foreach($node->children as $child)
            @include('livewire.category._tree-node', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
    @endif
</div>
