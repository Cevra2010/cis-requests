@php
$hasChildren = $node->children->isNotEmpty();
@endphp
<div x-data="{ open: true }" x-on:force-open="open = true"
     wire:key="lagerort-node-{{ $node->cis_row_id }}"
     data-id="{{ $node->cis_row_id }}"
     class="{{ $depth > 0 ? 'ml-6 border-l border-gray-100 pl-3' : '' }}">
    <div class="group flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-50">
        <span class="js-drag-handle w-4 h-4 flex items-center justify-center text-gray-300 hover:text-gray-500 cursor-grab shrink-0" title="Ziehen zum Verschieben">
            <i class="fa fa-grip-vertical text-[11px]"></i>
        </span>

        <button type="button" @click="open = !open"
                class="w-4 h-4 flex items-center justify-center text-gray-400 shrink-0 {{ ! $hasChildren ? 'invisible' : '' }}">
            <i class="fa fa-chevron-right text-[10px] transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>

        <i class="fa fa-box-archive text-gray-300 text-xs shrink-0"></i>
        <span class="text-sm font-medium text-gray-800">{{ $node->name }}</span>

        @if($hasChildren)
            <span class="text-[10px] text-gray-300 shrink-0">{{ $node->children->count() }}</span>
        @endif

        <a href="{{ route('lager.lagerort.label.pdf', $node->cis_row_id) }}" target="_blank" title="QR-Etikett drucken"
           class="ml-auto text-gray-300 hover:text-primary-600 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <i class="fa fa-qrcode text-xs"></i>
        </a>

        <a href="{{ route('lager.buchen', $node->cis_row_id) }}" title="Ware in diesen Lagerort buchen"
           class="text-gray-300 hover:text-primary-600 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <i class="fa fa-box-open text-xs"></i>
        </a>

        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            <button type="button" wire:click="openCreate('{{ $node->cis_row_id }}')" title="Untereintrag anlegen"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-primary-600">
                <i class="fa fa-plus text-xs"></i>
            </button>
            <button type="button" wire:click="openEdit('{{ $node->cis_row_id }}')" title="Bearbeiten"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-gray-700">
                <i class="fa fa-pencil text-xs"></i>
            </button>
            <button type="button" wire:click="confirmDelete('{{ $node->cis_row_id }}')" title="Löschen"
                    class="btn btn-ghost btn-sm !px-1.5 !py-1 text-gray-400 hover:text-red-500">
                <i class="fa fa-trash text-xs"></i>
            </button>
        </div>
    </div>

    {{-- Immer gerendert (auch leer) – muss als Drop-Ziel für Drag & Drop existieren. --}}
    <div class="js-sortable-children min-h-[6px]" data-parent-id="{{ $node->cis_row_id }}" x-show="open" x-cloak>
        @foreach($node->children as $child)
            @include('lager::livewire._lagerort-tree-node', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
</div>
