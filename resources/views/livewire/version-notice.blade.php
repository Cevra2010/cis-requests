<div>
    {{-- Kleiner, anklickbarer Versions-Tag – wird im Sidebar-Footer neben "Mein Konto" eingebunden. --}}
    <button type="button" wire:click="openHistory"
            class="text-[10px] text-gray-600 hover:text-gray-300 transition-colors shrink-0"
            title="Update-Notes ansehen">
        v{{ $currentVersion }}
    </button>

    {{-- Beide Modals werden per x-teleport ans Ende von <body> verschoben, damit sie unabhängig
         von der Position dieses kleinen Tags im Sidebar-Footer immer zentriert über der ganzen
         Seite erscheinen (gleiches Prinzip wie das Filter-Popover in data-table/th.blade.php).
         x-show bindet dafür über @entangle auf echte Alpine-Reaktivität statt auf ein bei
         jedem Render fest eingebranntes @js(...) – Livewires Morphdom aktualisiert Inhalte
         innerhalb von <template> nicht zuverlässig, @entangle umgeht das komplett. --}}
    <div x-data="{ show: @entangle('show'), showManual: @entangle('showManual') }">
        <template x-teleport="body">
            <div x-show="show" x-cloak
                 class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 px-4" style="display:none">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full max-h-[85vh] p-6 flex flex-col">
                    <div class="flex items-center gap-2 mb-1 shrink-0">
                        <i class="fa fa-rocket text-primary-500"></i>
                        <h3 class="text-base font-semibold text-gray-900">Neu in Version {{ $currentVersion }}</h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-4 shrink-0">Das hat sich seit Ihrem letzten Besuch geändert:</p>

                    <div class="space-y-4 overflow-y-auto pr-1 flex-1 min-h-0">
                        @foreach($entries as $section)
                            <div>
                                @if(count($entries) > 1)
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">
                                        Version {{ $section['version'] }}
                                        @if($section['date']) &middot; {{ \Illuminate\Support\Carbon::parse($section['date'])->format('d.m.Y') }} @endif
                                    </p>
                                @endif
                                <ul class="space-y-1.5">
                                    @foreach($section['items'] as $item)
                                        <li class="flex items-start gap-2 text-sm text-gray-700">
                                            <i class="fa fa-check text-green-500 text-xs mt-1 shrink-0"></i>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end mt-6 shrink-0">
                        <button type="button" wire:click="acknowledge" class="btn btn-primary btn-sm">
                            Verstanden
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div x-show="showManual" x-cloak
                 @click.self="$wire.closeHistory()" @keydown.escape.window="$wire.closeHistory()"
                 class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 px-4" style="display:none">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full max-h-[85vh] p-6 flex flex-col">
                    <div class="flex items-center justify-between mb-1 shrink-0">
                        <div class="flex items-center gap-2">
                            <i class="fa fa-clock-rotate-left text-gray-400"></i>
                            <h3 class="text-base font-semibold text-gray-900">Update-Notes</h3>
                        </div>
                        <button type="button" wire:click="closeHistory" class="text-gray-300 hover:text-gray-600">
                            <i class="fa fa-xmark"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mb-4 shrink-0">Aktuelle Version: {{ $currentVersion }}</p>

                    <div class="space-y-4 overflow-y-auto pr-1 flex-1 min-h-0">
                        @forelse($entries as $section)
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">
                                    Version {{ $section['version'] }}
                                    @if($section['date']) &middot; {{ \Illuminate\Support\Carbon::parse($section['date'])->format('d.m.Y') }} @endif
                                </p>
                                <ul class="space-y-1.5">
                                    @foreach($section['items'] as $item)
                                        <li class="flex items-start gap-2 text-sm text-gray-700">
                                            <i class="fa fa-check text-green-500 text-xs mt-1 shrink-0"></i>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 italic text-center py-6">Kein Änderungsprotokoll vorhanden.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
