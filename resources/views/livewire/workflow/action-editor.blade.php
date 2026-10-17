<div>
    @if($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">
                    <i class="fa fa-{{ $actionIcon }} text-gray-400 mr-1.5"></i>{{ $actionLabel }}
                </h3>
                <button type="button" wire:click="close" class="text-gray-300 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
            </div>

            <div class="space-y-4">
                @if($editorView)
                    @include($editorView)
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-100">
                <button type="button" wire:click="close" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="save" class="btn btn-primary btn-sm">Speichern</button>
            </div>
        </div>
    </div>
    @endif
</div>
