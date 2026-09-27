<div>
    @if($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">
                    <i class="fa fa-envelope text-gray-400 mr-1.5"></i>{{ $actionLabel }}
                </h3>
                <button type="button" wire:click="close" class="text-gray-300 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="cis-label">Empfänger</label>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        @forelse($recipients as $i => $r)
                            <span wire:key="recipient-{{ $i }}"
                                  class="inline-flex items-center gap-1.5 text-xs bg-gray-50 text-gray-700 border border-gray-200 rounded-full pl-2.5 pr-1.5 py-1">
                                {{ $r['label'] }}
                                <button type="button" wire:click="removeRecipient({{ $i }})" class="text-gray-300 hover:text-red-500">
                                    <i class="fa fa-xmark text-[10px]"></i>
                                </button>
                            </span>
                        @empty
                            <p class="text-xs text-gray-400 italic">Noch keine Empfänger.</p>
                        @endforelse
                    </div>
                    @livewire('workflow.principal-picker', ['context' => 'action-recipients', 'allowEmail' => true], key('action-recipient-picker-' . $actionId))
                    @error('recipients')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @if(isset($formErrors['recipients']))<p class="mt-1 text-xs text-red-600">{{ $formErrors['recipients'] }}</p>@endif
                </div>

                <div x-data="{ target: 'body' }">
                    <div>
                        <label class="cis-label">Betreff</label>
                        <input type="text" wire:model="subject" x-on:focus="target = 'subject'" class="cis-input w-full">
                        @if(isset($formErrors['subject']))<p class="mt-1 text-xs text-red-600">{{ $formErrors['subject'] }}</p>@endif
                    </div>

                    <div class="mt-4">
                        <label class="cis-label">Text</label>
                        <textarea wire:model="body" x-on:focus="target = 'body'" rows="6" class="cis-input w-full"></textarea>
                        @if(isset($formErrors['body']))<p class="mt-1 text-xs text-red-600">{{ $formErrors['body'] }}</p>@endif
                    </div>

                    <div class="mt-3">
                        <p class="text-[11px] text-gray-400 mb-1.5">Projektvariable einfügen in <span x-text="target === 'subject' ? 'Betreff' : 'Text'"></span>:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($variables as $key => $label)
                                <button type="button" @click="$wire.insertVariable(target, '{{ $key }}')"
                                        class="text-[11px] px-2 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-100">
                <button type="button" wire:click="close" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button" wire:click="save" class="btn btn-primary btn-sm">Speichern</button>
            </div>
        </div>
    </div>
    @endif
</div>
