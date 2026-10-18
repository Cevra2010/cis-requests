<div class="max-w-3xl" x-data="{ sub: new URLSearchParams(window.location.search).get('vorlagen') || 'pre_tender' }">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-800">Export-Import: Vorlagen</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Definiere Tabellen mit eigenen Spaltennamen, Filtern und Sortierung für den CSV-/Excel-Export.
            Vorlagen "vor der Ausschreibung" sind für die ausgehende Tabelle an Anbieter gedacht (inkl.
            leerer Preisfelder zum Ausfüllen); Vorlagen "nach der Ausschreibung/Auswertung" für den
            Ergebnis-Export nach Zuschlag. Jede Vorlage steht automatisch im Dokumentenmanager jedes
            Projekts zur Verfügung. Ob eine Vorlage einen technischen Import-Schlüssel in die Tabelle
            schreibt, wird je Vorlage unter „Bearbeiten“ festgelegt. Enthält eine Vorlage zusätzlich die
            Felder „Einzelpreis (Händler)" oder „Gesamtpreis (Händler)", kann die vom Händler ausgefüllte
            Datei im Angebotsvergleich des Projekts wieder eingelesen werden.
        </p>
    </div>

    {{-- Unter-Tabs: Vorlagen nach Verwendungszweck --}}
    <div class="flex items-center gap-1 border border-gray-200 rounded-lg p-1 bg-gray-50 mb-5 w-fit max-w-full flex-wrap">
        @foreach($phases as $phaseKey => $phaseLabel)
        <button type="button" @click="sub = '{{ $phaseKey }}'"
                :class="sub === '{{ $phaseKey }}' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500 hover:text-gray-700'"
                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">
            {{ $phaseLabel }}
            <span class="ml-1 text-[11px] text-gray-400">{{ $templatesByPhase->get($phaseKey, collect())->count() }}</span>
        </button>
        @endforeach
        <button type="button" @click="sub = 'datasets'"
                :class="sub === 'datasets' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500 hover:text-gray-700'"
                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors">
            Systemdaten
            <span class="ml-1 text-[11px] text-gray-400">{{ $templatesByDataset->flatten(1)->count() }}</span>
        </button>
    </div>

    @foreach($phases as $phaseKey => $phaseLabel)
    <div class="mb-8" x-show="sub === '{{ $phaseKey }}'" x-cloak>

        {{-- ── Neue Vorlage ── --}}
        <div class="cis-card mb-3">
            <div class="flex items-center gap-2">
                <input type="text" wire:model="newTemplateNames.{{ $phaseKey }}" placeholder="Name der Vorlage, z. B. Standard-Tabelle"
                       class="cis-input flex-1 @error('newTemplateNames.' . $phaseKey) is-invalid @enderror">
                <button type="button" wire:click="createTemplate('{{ $phaseKey }}')" class="btn btn-primary btn-sm shrink-0">
                    <i class="fa fa-plus mr-1.5"></i> Vorlage anlegen
                </button>
            </div>
            @error('newTemplateNames.' . $phaseKey)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- ── Vorlagenliste dieser Phase ── --}}
        @forelse($templatesByPhase->get($phaseKey, collect()) as $template)
        @include('export::partials.template-card', ['template' => $template])
        @empty
        <div class="cis-card text-center py-8 text-gray-400">
            <i class="fa fa-table text-2xl mb-2 block"></i>
            <p class="text-sm">Noch keine Vorlagen in dieser Phase.</p>
        </div>
        @endforelse
    </div>
    @endforeach

    {{-- ── Systemdaten (Import-/Export-Werkzeug) ── --}}
    <div x-show="sub === 'datasets'" x-cloak>
    <p class="text-sm text-gray-500 mb-4">
        Vorlagen für Systemdaten (Benutzer, Rollen, Gruppen, Projekte, Produkte, Lager) – sie erscheinen im Tab „Import / Export“,
        nicht in den Projekten.
    </p>
    @foreach($datasets as $datasetKey => $datasetLabel)
    <div class="mb-8">
        <h3 class="text-sm font-semibold text-gray-700 mb-0.5">Systemdaten: {{ $datasetLabel }}</h3>
        <p class="text-xs text-gray-400 mb-2">Vorlagen auf allen {{ $datasetLabel }} des Systems – für das Import-/Export-Werkzeug, nicht für Projekte.</p>

        <div class="cis-card mb-3">
            <div class="flex items-center gap-2">
                <input type="text" wire:model="newDatasetTemplateNames.{{ $datasetKey }}" placeholder="Name der Vorlage, z. B. Preisliste"
                       class="cis-input flex-1 @error('newDatasetTemplateNames.' . $datasetKey) is-invalid @enderror">
                <button type="button" wire:click="createDatasetTemplate('{{ $datasetKey }}')" class="btn btn-primary btn-sm shrink-0">
                    <i class="fa fa-plus mr-1.5"></i> Vorlage anlegen
                </button>
            </div>
            @error('newDatasetTemplateNames.' . $datasetKey)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        @forelse($templatesByDataset->get($datasetKey, collect()) as $template)
        @include('export::partials.template-card', ['template' => $template])
        @empty
        <div class="cis-card text-center py-8 text-gray-400">
            <i class="fa fa-table text-2xl mb-2 block"></i>
            <p class="text-sm">Noch keine Vorlagen für diese Daten.</p>
        </div>
        @endforelse
    </div>
    @endforeach
    </div>
</div>
