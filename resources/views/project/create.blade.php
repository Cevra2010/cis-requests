@extends('layout.app')

@section('title', 'Neues Projekt')

@section('content')
<div class="max-w-2xl">
    <div class="cis-card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Neues Projekt anlegen</h2>

        <form action="{{ route('project.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Name --}}
            <div>
                <label class="cis-label" for="name">Projektname <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name') }}" required placeholder="z.B. Beschaffung HLF 20 – Feuerwehr Musterstadt">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Beschreibung --}}
            <div>
                <label class="cis-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="cis-input w-full" rows="3"
                          placeholder="Optionale Kurzbeschreibung des Projekts…">{{ old('description') }}</textarea>
            </div>

            {{-- Typ --}}
            <div>
                <label class="cis-label">Art der Ausschreibung <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(\App\Models\Project::TYPES as $typeKey => $typeLabel)
                    <label class="flex items-center gap-2.5 px-4 py-3 rounded-xl border cursor-pointer transition-colors
                                  {{ old('type', 'product') === $typeKey ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="radio" name="type" value="{{ $typeKey }}"
                               {{ old('type', 'product') === $typeKey ? 'checked' : '' }} class="text-primary-600">
                        <span class="text-sm font-medium text-gray-800">{{ $typeLabel }}</span>
                    </label>
                    @endforeach
                </div>
                <p class="mt-1.5 text-xs text-gray-400">Kann nach dem Anlegen nicht mehr geändert werden.</p>
                @error('type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Status + Kategorie --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="cis-label" for="status_code">Status <span class="text-red-500">*</span></label>
                    <select id="status_code" name="status_code" class="cis-input w-full" required>
                        @foreach($statuses as $code => $meta)
                            <option value="{{ $code }}" {{ old('status_code', 'draft') === $code ? 'selected' : '' }}>
                                {{ $meta['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="cis-label" for="category_id">Kategorie</label>
                    <x-cis-category-select type="project.category" name="category_id"
                                           :value="old('category_id')" />
                </div>
            </div>

            {{-- Verantwortlicher (AssigneeSearch) --}}
            <div>
                <label class="cis-label">Verantwortliche(r)</label>
                @livewire('project.assignee-search', [
                    'assigneeType' => old('assignee_type'),
                    'assigneeId'   => old('assignee_id'),
                ])
                @error('assignee_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Auftraggeber + Jahr + Fälligkeit --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="cis-label" for="client">Auftraggeber</label>
                    <input type="text" id="client" name="client" class="cis-input w-full"
                           value="{{ old('client') }}" placeholder="z.B. Gemeinde Musterstadt">
                </div>
                <div>
                    <label class="cis-label" for="tender_year">Ausschreibungsjahr</label>
                    <input type="number" id="tender_year" name="tender_year" class="cis-input w-full"
                           value="{{ old('tender_year', date('Y')) }}" min="2000" max="2100">
                </div>
                <div>
                    <label class="cis-label" for="due_date">Fälligkeitsdatum</label>
                    <input type="date" id="due_date" name="due_date" class="cis-input w-full"
                           value="{{ old('due_date') }}">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Projekt erstellen</button>
                <a href="{{ route('project') }}" class="btn btn-ghost">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
