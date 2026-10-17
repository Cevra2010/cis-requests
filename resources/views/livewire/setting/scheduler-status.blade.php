<div class="max-w-3xl space-y-5" wire:poll.15s>
    {{-- Status --}}
    <div class="cis-card">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-base font-semibold text-gray-800">Planer (Cron)</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Zeitgesteuerte Aufgaben – z.B. Workflow-Trigger „Ausschreibungszeitraum hat begonnen/ist geendet“ – laufen über den Laravel-Scheduler,
                    den der Server jede Minute per Cron aufrufen muss.
                </p>
            </div>
            @php
                $badge = match($state) {
                    'ok'      => ['bg-emerald-100 text-emerald-700', 'fa-circle-check', 'Läuft regelmäßig'],
                    'delayed' => ['bg-amber-100 text-amber-700', 'fa-triangle-exclamation', 'Verzögert'],
                    'down'    => ['bg-red-100 text-red-700', 'fa-circle-xmark', 'Läuft nicht'],
                    default   => ['bg-gray-100 text-gray-500', 'fa-circle-question', 'Noch nie gelaufen'],
                };
            @endphp
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium {{ $badge[0] }}">
                <i class="fa {{ $badge[1] }}"></i>{{ $badge[2] }}
            </span>
        </div>

        <dl class="grid grid-cols-3 gap-4 mt-5 text-sm">
            <div>
                <dt class="text-xs text-gray-400">Letzte Ausführung</dt>
                <dd class="text-gray-800 font-medium">{{ $last ? $last->ran_at->format('d.m.Y H:i:s') : '–' }}</dd>
                @if($last)<dd class="text-xs text-gray-400">vor {{ $ageSec < 120 ? $ageSec . ' s' : round($ageSec / 60) . ' min' }} · {{ $last->sourceLabel() }}</dd>@endif
            </div>
            <div>
                <dt class="text-xs text-gray-400">Ausführungen letzte Stunde</dt>
                <dd class="text-gray-800 font-medium">{{ $runsLastHour }} <span class="text-gray-400 font-normal">von 60 erwartet</span></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Größte Lücke (letzte Stunde)</dt>
                <dd class="text-gray-800 font-medium">{{ $maxGap !== null ? ($maxGap < 120 ? $maxGap . ' s' : round($maxGap / 60, 1) . ' min') : '–' }}</dd>
            </div>
        </dl>

        @if($state !== 'ok')
            <p class="mt-4 text-xs text-gray-500">
                @if($state === 'none') Der Scheduler wurde noch nie ausgeführt. Richte den Cron-Eintrag unten ein oder starte ihn über „Jetzt ausführen“.
                @else Die letzte Ausführung ist zu lange her – prüfe den Cron-Eintrag auf dem Server. Bis dahin werden fällige Trigger nur beim Öffnen von Projektliste/Projekt nachgeholt.
                @endif
            </p>
        @endif

        <div class="mt-4">
            <button type="button" wire:click="runNow" wire:loading.attr="disabled" class="btn btn-ghost btn-sm">
                <i class="fa fa-play mr-1.5"></i>Jetzt ausführen
            </button>
        </div>
    </div>

    {{-- Cron-Befehl --}}
    <div class="cis-card">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Cron-Eintrag (jede Minute)</h3>
        <p class="text-xs text-gray-500 mb-2">Auf dem Server mit <code>crontab -e</code> für den Benutzer eintragen, unter dem die Anwendung läuft:</p>
        <pre class="text-xs bg-gray-900 text-gray-100 rounded-lg px-3 py-2.5 overflow-x-auto select-all">{{ $cronLine }}</pre>
        <p class="text-[11px] text-gray-400 mt-2">Der PHP-Pfad entspricht dem aktuell verwendeten PHP – auf dem Server ggf. anpassen (z.B. <code>/usr/bin/php</code>).</p>

        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mt-4 mb-1.5">Geplante Aufgaben</h4>
        <ul class="text-sm text-gray-700 space-y-1">
            @foreach($schedule as $event)
                <li><i class="fa fa-clock text-gray-300 mr-1.5"></i>{{ $event->description ?: $event->getSummaryForDisplay() }}
                    <span class="text-xs text-gray-400">· Zeitplan <code>{{ $event->expression }}</code></span></li>
            @endforeach
        </ul>
    </div>

    {{-- Cron-URL (nur Entwicklungsumgebung) --}}
    @if($urlAvailable)
    <div class="cis-card">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Cron-Trigger-URL (Entwicklung)</h3>
                <p class="text-xs text-gray-500">Ruft man diese URL auf, läuft der Scheduler einmal – z.B. per Browser, <code>curl</code> oder einem externen Cron-Dienst. Nur in der Entwicklungsumgebung verfügbar.</p>
            </div>
            <button type="button" wire:click="toggleUrl" class="btn {{ $urlEnabled ? 'btn-primary' : 'btn-ghost' }} btn-sm shrink-0">
                {{ $urlEnabled ? 'Aktiv' : 'Inaktiv' }}
            </button>
        </div>

        @if($urlEnabled && $url)
            <pre class="text-xs bg-gray-50 border border-gray-200 text-gray-700 rounded-lg px-3 py-2.5 mt-3 overflow-x-auto select-all">{{ $url }}</pre>
            <button type="button" wire:click="regenerateToken" wire:confirm="Neues Token erzeugen? Die bisherige URL funktioniert danach nicht mehr."
                    class="mt-2 text-xs text-gray-400 hover:text-red-500"><i class="fa fa-rotate mr-1"></i>Token neu erzeugen</button>
        @endif
    </div>

    @endif

    {{-- Verlauf --}}
    <div class="cis-card">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Letzte Ausführungen</h3>
        @forelse($recent as $run)
            <div class="flex items-center gap-3 py-1.5 text-xs border-b border-gray-50 last:border-0">
                <i class="fa {{ $run->status === 'ok' ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-red-500' }}"></i>
                <span class="text-gray-700 tabular-nums">{{ $run->ran_at->format('d.m.Y H:i:s') }}</span>
                <span class="text-gray-600 truncate flex-1">{{ $run->task }}@if($run->message) <span class="text-red-500">– {{ $run->message }}</span>@endif</span>
                <span class="text-gray-400">{{ $run->sourceLabel() }}</span>
                <span class="text-gray-400 tabular-nums w-14 text-right">{{ $run->duration_ms !== null ? $run->duration_ms . ' ms' : '' }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400 italic">Noch keine Ausführungen protokolliert.</p>
        @endforelse
    </div>
</div>
