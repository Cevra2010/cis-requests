@php
$hasChildren = $node->children->isNotEmpty();
@endphp
<div x-data="{ open: {{ $depth === 0 ? 'true' : 'false' }} }" wire:key="pbrowse-tree-{{ $node->cis_row_id }}" class="{{ $depth > 0 ? 'ml-4 border-l border-gray-100 pl-2' : '' }}">
    <div class="group flex items-center gap-1.5 py-1.5 px-2 rounded-lg hover:bg-gray-50">
        <button type="button" @click="open = !open"
                class="w-3.5 h-3.5 flex items-center justify-center text-gray-400 shrink-0 {{ ! $hasChildren ? 'invisible' : '' }}">
            <i class="fa fa-chevron-right text-[9px] transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>

        <div class="flex-1 min-w-0">
            <span class="text-sm text-gray-800">{{ $node->name }}</span>
            @if($node->category)<span class="ml-1.5 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ $node->category->name }}</span>@endif
            @if($node->description)<p class="text-xs text-gray-400 truncate">{{ $node->description }}</p>@endif
        </div>

        <button type="button" wire:click="insertParameter('{{ $node->cis_row_id }}')"
                class="btn btn-primary btn-sm shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            Übernehmen
        </button>
    </div>

    @if($hasChildren)
    <div x-show="open" x-cloak>
        @foreach($node->children as $child)
            @include('livewire.project._parameter-browser-node', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
    @endif
</div>
