<div>
    @if($show)
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center gap-2 mb-1">
                <i class="fa fa-rocket text-primary-500"></i>
                <h3 class="text-base font-semibold text-gray-900">Neu in Version {{ $currentVersion }}</h3>
            </div>
            <p class="text-xs text-gray-500 mb-4">Das hat sich seit Ihrem letzten Besuch geändert:</p>

            <div class="space-y-4 max-h-80 overflow-y-auto pr-1">
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

            <div class="flex justify-end mt-6">
                <button type="button" wire:click="acknowledge" class="btn btn-primary btn-sm">
                    Verstanden
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
