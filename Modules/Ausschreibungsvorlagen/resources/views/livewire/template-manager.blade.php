<div class="max-w-3xl">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-800">Ausschreibungsvorlagen</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Vorlagen bestehen aus Blöcken (Überschrift, Text, Abstand, Materialliste) und lassen sich
            im Reiter „Ausschreibung" eines Projekts mit einem Klick übernehmen.
        </p>
    </div>

    {{-- ── Neue Vorlage ── --}}
    <div class="cis-card mb-4">
        <div class="flex items-center gap-2">
            <input type="text" wire:model="newTemplateName" placeholder="Name der Vorlage, z. B. Standard-Ausschreibung"
                   class="cis-input flex-1 @error('newTemplateName') is-invalid @enderror">
            <button type="button" wire:click="createTemplate" class="btn btn-primary btn-sm shrink-0">
                <i class="fa fa-plus mr-1.5"></i> Vorlage anlegen
            </button>
        </div>
        @error('newTemplateName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- ── Vorlagenliste ── --}}
    @forelse($templates as $template)
    @php $isExpanded = $expandedTemplateId === $template->cis_row_id; @endphp
    <div class="cis-card mb-3" wire:key="tpl-{{ $template->cis_row_id }}">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                    <i class="fa fa-file-lines text-primary-500 text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <input type="text" value="{{ $template->name }}"
                           wire:change="renameTemplate('{{ $template->cis_row_id }}', $event.target.value)"
                           class="text-sm font-medium text-gray-800 border-0 bg-transparent px-0 py-0 focus:ring-0 w-full">
                    <p class="text-xs text-gray-400">{{ $template->blocks->count() }} Block/Blöcke</p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" wire:click="toggleExpanded('{{ $template->cis_row_id }}')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-{{ $isExpanded ? 'chevron-up' : 'chevron-down' }} mr-1.5"></i>
                    Blöcke
                </button>
                <button type="button" wire:click="deleteTemplate('{{ $template->cis_row_id }}')"
                        wire:confirm="Vorlage „{{ addslashes($template->name) }}“ wirklich löschen?"
                        class="text-gray-300 hover:text-red-500 transition-colors" title="Löschen">
                    <i class="fa fa-trash-can text-xs"></i>
                </button>
            </div>
        </div>

        @if($isExpanded)
        <div class="mt-4 pt-4 border-t border-gray-100">
            @if($template->blocks->isEmpty())
                <p class="text-xs text-gray-400 italic mb-3">Noch keine Blöcke.</p>
            @else
                <div class="space-y-1.5 mb-4">
                    @foreach($template->blocks as $block)
                    @php
                        $bIcon  = match($block->type) { 'heading' => 'fa-heading', 'text' => 'fa-align-left', 'space' => 'fa-arrows-up-down', default => 'fa-boxes-stacked' };
                        $bColor = match($block->type) { 'heading' => 'text-indigo-500', 'text' => 'text-violet-500', 'space' => 'text-gray-400', default => 'text-amber-500' };
                        $bLabel = match($block->type) { 'heading' => 'Überschrift', 'text' => 'Text', 'space' => 'Abstand', default => 'Materialliste (Platzhalter)' };
                    @endphp
                    <div wire:key="blk-{{ $block->cis_row_id }}"
                         class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg bg-gray-50 border border-gray-100">
                        <i class="fa {{ $bIcon }} {{ $bColor }} text-xs mt-1.5 shrink-0"></i>

                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">{{ $bLabel }}</p>

                            @if($block->type === 'heading')
                                <input type="text" value="{{ $block->config['text'] ?? '' }}"
                                       wire:change="updateBlockText('{{ $block->cis_row_id }}', $event.target.value)"
                                       placeholder="Überschriftstext"
                                       class="cis-input text-sm w-full">
                            @elseif($block->type === 'text')
                                <textarea wire:change="updateBlockText('{{ $block->cis_row_id }}', $event.target.value)"
                                          rows="2" placeholder="Freitext"
                                          class="cis-input text-sm w-full resize-none">{{ $block->config['text'] ?? '' }}</textarea>
                            @elseif($block->type === 'space')
                                <div class="flex items-center gap-2">
                                    <input type="number" min="10" max="400" value="{{ $block->config['height'] ?? 40 }}"
                                           wire:change="updateSpaceHeight('{{ $block->cis_row_id }}', $event.target.value)"
                                           class="cis-input text-sm w-24">
                                    <span class="text-xs text-gray-400">px</span>
                                </div>
                            @else
                                <p class="text-xs text-gray-400 italic">
                                    Wird beim Übernehmen der Vorlage durch die tatsächlichen Produkte des Projekts ersetzt.
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center gap-1 shrink-0 pt-1.5">
                            <button type="button" wire:click="moveBlock('{{ $block->cis_row_id }}', 'up')"
                                    class="text-gray-300 hover:text-gray-600 w-5 text-center"><i class="fa fa-arrow-up text-[10px]"></i></button>
                            <button type="button" wire:click="moveBlock('{{ $block->cis_row_id }}', 'down')"
                                    class="text-gray-300 hover:text-gray-600 w-5 text-center"><i class="fa fa-arrow-down text-[10px]"></i></button>
                            <button type="button" wire:click="removeBlock('{{ $block->cis_row_id }}')"
                                    class="text-gray-300 hover:text-red-500 w-5 text-center"><i class="fa fa-xmark text-[10px]"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-1.5">
                <button type="button" wire:click="addBlock('{{ $template->cis_row_id }}', 'heading')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-heading mr-1"></i> Überschrift
                </button>
                <button type="button" wire:click="addBlock('{{ $template->cis_row_id }}', 'text')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-align-left mr-1"></i> Text
                </button>
                <button type="button" wire:click="addBlock('{{ $template->cis_row_id }}', 'space')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-arrows-up-down mr-1"></i> Abstand
                </button>
                <button type="button" wire:click="addBlock('{{ $template->cis_row_id }}', 'products')" class="btn btn-ghost btn-sm">
                    <i class="fa fa-boxes-stacked mr-1"></i> Materialliste
                </button>
            </div>
        </div>
        @endif
    </div>
    @empty
    <div class="cis-card text-center py-12 text-gray-400">
        <i class="fa fa-file-lines text-3xl mb-3 block"></i>
        <p class="text-sm">Noch keine Ausschreibungsvorlagen angelegt.</p>
    </div>
    @endforelse
</div>
