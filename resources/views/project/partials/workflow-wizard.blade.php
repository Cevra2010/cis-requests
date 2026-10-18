{{-- Projektverlauf als Wizard – siehe App\Services\Workflow\ProjectWorkflowView. --}}
<div x-data="{ confirmOpen: false, pending: null, historyOpen: false }">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-800">
            Projektverlauf
            <span class="text-xs font-normal text-gray-400 ml-1">Schritt {{ $wizard->stepNumber() }} von {{ $wizard->totalSteps() }}</span>
        </h2>
        <button type="button" @click="historyOpen = true" class="text-xs text-gray-400 hover:text-gray-600">
            <i class="fa fa-clock-rotate-left mr-1"></i>Verlauf
        </button>
    </div>

    {{-- Stepper --}}
    <div class="flex items-start overflow-x-auto pt-2 pb-1 px-2 -mx-2">
        @foreach($wizard->steps as $i => $step)
            @php
                $isCurrent = $step->code === $project->status_code;
                $isPast    = $i < $wizard->stepNumber() - 1;
                $isNext    = $wizard->next && $step->code === $wizard->next->code;
                $isPrev    = $wizard->previous && $step->code === $wizard->previous->code;
            @endphp
            <div class="flex items-center {{ ! $loop->last ? 'flex-1' : '' }} min-w-[96px]">
                <div class="flex flex-col items-center shrink-0 w-24">
                    <button type="button"
                            @if($isNext && $wizard->canAdvance) @click="pending = 'advance'; confirmOpen = true"
                            @elseif($isPrev && $wizard->canRevert) @click="pending = 'revert'; confirmOpen = true"
                            @else disabled @endif
                            title="{{ $step->label }}"
                            class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-semibold shrink-0 relative
                                   {{ $isCurrent ? 'bg-primary-600 text-white ring-4 ring-primary-100' : ($isPast ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400') }}
                                   {{ ($isNext && $wizard->canAdvance) || ($isPrev && $wizard->canRevert) ? 'cursor-pointer hover:ring-2 hover:ring-primary-200' : '' }}
                                   {{ ($isNext && ! $wizard->canAdvance) ? 'cursor-not-allowed' : '' }}">
                        @if($isPast)
                            <i class="fa fa-check text-[9px]"></i>
                        @elseif($isNext && ! $wizard->canAdvance)
                            <i class="fa fa-lock text-[9px]"></i>
                        @else
                            {{ $i + 1 }}
                        @endif
                        @if(! empty($wizard->stepFixations[$step->code] ?? []))
                            <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-indigo-500 text-white text-[7px] flex items-center justify-center">
                                <i class="fa fa-lock"></i>
                            </span>
                        @endif
                    </button>
                    <span class="mt-1.5 px-1 max-w-full text-[10px] text-center leading-tight [overflow-wrap:anywhere] {{ $isCurrent ? 'text-gray-800 font-semibold' : 'text-gray-400' }}">
                        {{ $step->label }}
                    </span>
                </div>
                @if(! $loop->last)
                    <div class="flex-1 h-0.5 rounded {{ $isPast ? 'bg-primary-200' : 'bg-gray-100' }}" style="margin-top: -14px;"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Aktueller Schritt --}}
    <div class="mt-4 p-4 rounded-xl bg-gray-50 flex items-start justify-between gap-4 flex-wrap">
        <div class="min-w-0">
            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Aktueller Schritt</p>
            <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $wizard->current?->label ?? $project->statusLabel() }}</p>
            @if($wizard->current?->description)
                <p class="text-xs text-gray-500 mt-0.5">{{ $wizard->current->description }}</p>
            @endif
            @if($project->isLocked())
                <p class="mt-1.5 text-xs text-gray-400">
                    <i class="fa fa-lock mr-1"></i>Fixiert seit {{ $project->tender_locked_at?->format('d.m.Y H:i') }}
                    @if($project->lockedByUser())
                        von {{ $project->lockedByUser()->name() }}
                    @endif
                </p>
            @endif
            @foreach($wizard->scheduled as $item)
                <p class="mt-1.5 text-xs text-gray-400">
                    <i class="fa fa-clock mr-1"></i>{{ $item['when']->format('d.m.Y H:i') }} Uhr – {{ $item['event'] }}@if(count($item['actions'])): {{ implode(', ', $item['actions']) }}@endif
                </p>
            @endforeach
        </div>

        <div class="flex flex-col items-end gap-1.5 shrink-0">
            <div class="flex items-center gap-2">
                @if($wizard->previous)
                    <form method="POST" action="{{ route('project.status.revert', $project->cis_row_id) }}" x-ref="revertForm">
                        @csrf
                    </form>
                    <button type="button"
                            @if($wizard->canRevert) @click="pending = 'revert'; confirmOpen = true" @else disabled @endif
                            class="btn btn-ghost btn-sm {{ ! $wizard->canRevert ? 'opacity-40 cursor-not-allowed' : '' }}">
                        <i class="fa fa-arrow-left mr-1.5"></i>Zurück
                    </button>
                @endif

                @if($wizard->next)
                    <form method="POST" action="{{ route('project.status.advance', $project->cis_row_id) }}" x-ref="advanceForm">
                        @csrf
                    </form>
                    <button type="button"
                            @if($wizard->canAdvance) @click="pending = 'advance'; confirmOpen = true" @else disabled @endif
                            class="btn btn-primary btn-sm {{ ! $wizard->canAdvance ? 'opacity-40 cursor-not-allowed' : '' }}">
                        @if(! empty($wizard->nextFixes))<i class="fa fa-lock mr-1.5"></i>Weiter: {{ $wizard->next->label }}
                        @else Weiter: {{ $wizard->next->label }}<i class="fa fa-arrow-right ml-1.5"></i>
                        @endif
                    </button>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-50 text-green-700 text-sm font-medium">
                        <i class="fa fa-circle-check"></i>Projekt abgeschlossen
                    </span>
                @endif
            </div>

            @if($wizard->next && ! $wizard->canAdvance)
                <p class="text-xs text-red-500 text-right max-w-xs">{{ implode(' ', $wizard->advanceBlockedReasons) }}</p>
            @endif
            @if($wizard->previous && ! $wizard->canRevert)
                <p class="text-xs text-red-500 text-right max-w-xs">{{ implode(' ', $wizard->revertBlockedReasons) }}</p>
            @endif
        </div>
    </div>

    {{-- Bestätigungs-Modal --}}
    <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6" @click.outside="confirmOpen = false">
            <template x-if="pending === 'advance'">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        @if(! empty($wizard->nextFixes))
                            <i class="fa fa-lock text-indigo-500"></i>
                            <h3 class="text-base font-semibold text-gray-900">Fixieren und weiter?</h3>
                        @else
                            <i class="fa fa-arrow-right text-primary-500"></i>
                            <h3 class="text-base font-semibold text-gray-900">Status ändern?</h3>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500">
                        @if(! empty($wizard->nextFixes))
                            Mit „{{ $wizard->next?->label }}“ werden fixiert:
                            <strong>{{ collect($wizard->nextFixes)->map(fn ($s) => \App\Services\Workflow\Actions\FixationAction::what($s))->join(', ') }}</strong>.
                            Sie sind danach für alle gesperrt, bis die Fixierung durch Zurücksetzen des Status wieder aufgehoben wird.
                        @else
                            Status wird auf „{{ $wizard->next?->label }}“ gesetzt.
                        @endif
                    </p>
                    @if(count($wizard->nextActions))
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-xs font-medium text-gray-600 mb-1.5">Folgende Maßnahmen werden ausgeführt:</p>
                            <ul class="space-y-1">
                                @foreach($wizard->nextActions as $a)
                                    <li class="text-xs text-gray-500 flex items-center gap-1.5">
                                        <i class="fa fa-{{ $a['icon'] }} text-gray-300"></i>{{ $a['summary'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </template>
            <template x-if="pending === 'revert'">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fa fa-rotate-left text-gray-500"></i>
                        <h3 class="text-base font-semibold text-gray-900">Status zurücksetzen?</h3>
                    </div>
                    <p class="text-sm text-gray-500">
                        Status wird zurück auf „{{ $wizard->previous?->label }}“ gesetzt.
                        @if(! empty($wizard->prevOpens))
                            Dadurch werden wieder bearbeitbar:
                            <strong>{{ collect($wizard->prevOpens)->map(fn ($s) => \App\Services\Workflow\Actions\FixationAction::what($s))->join(', ') }}</strong>.
                        @endif
                    </p>
                </div>
            </template>

            <div class="flex items-center justify-end gap-2 mt-5">
                <button type="button" @click="confirmOpen = false" class="btn btn-ghost btn-sm">Abbrechen</button>
                <button type="button"
                        @click="pending === 'advance' ? $refs.advanceForm.submit() : $refs.revertForm.submit()"
                        class="btn btn-primary btn-sm">
                    Bestätigen
                </button>
            </div>
        </div>
    </div>

    {{-- Verlauf-Modal --}}
    <div x-show="historyOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6" @click.outside="historyOpen = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Verlauf</h3>
                <button type="button" @click="historyOpen = false" class="text-gray-300 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($wizard->history as $entry)
                    <div class="flex items-start gap-2.5 text-sm">
                        <i class="fa {{ $entry->directionIcon() }} text-gray-300 mt-0.5 w-4 text-center"></i>
                        <div class="min-w-0">
                            <p class="text-gray-700">
                                @if($entry->from_label)
                                    <span class="text-gray-400">{{ $entry->from_label }} →</span>
                                @endif
                                <span class="font-medium">{{ $entry->to_label }}</span>
                            </p>
                            <p class="text-xs text-gray-400">{{ $entry->created_at->format('d.m.Y H:i') }} · {{ $entry->actorLabel() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">Kein Verlauf vorhanden.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
