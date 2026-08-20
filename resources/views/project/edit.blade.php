@extends('layout.app')

@section('title', $project->name)

@section('header_actions')
    <form method="POST" action="{{ route('project.duplicate', $project->cis_row_id) }}" class="inline">
        @csrf
        <button type="submit" class="btn btn-ghost btn-sm">
            <i class="fa fa-copy mr-1"></i> Duplizieren
        </button>
    </form>
    <form method="POST" action="{{ route('project.destroy', $project->cis_row_id) }}" class="inline"
          onsubmit="return confirm('Projekt in den Papierkorb verschieben?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">
            <i class="fa fa-trash mr-1"></i> Löschen
        </button>
    </form>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="cis-card">
        <div class="flex items-center gap-3 mb-5">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $project->statusBadgeClasses() }}">
                {{ $project->statusLabel() }}
            </span>
            <span class="text-xs text-gray-400">Erstellt {{ $project->created_at->format('d.m.Y') }}</span>
            <span class="text-xs text-gray-400">· {{ $project->typeLabel() }}</span>
        </div>

        <form action="{{ route('project.update', $project->cis_row_id) }}" method="POST" class="space-y-5">
            @csrf

            {{-- Name --}}
            <div>
                <label class="cis-label" for="name">Projektname <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" class="cis-input w-full"
                       value="{{ old('name', $project->name) }}" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Beschreibung --}}
            <div>
                <label class="cis-label" for="description">Beschreibung</label>
                <textarea id="description" name="description" class="cis-input w-full" rows="3">{{ old('description', $project->description) }}</textarea>
            </div>

            {{-- Status + Kategorie --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="cis-label" for="status_code">Status <span class="text-red-500">*</span></label>
                    <select id="status_code" name="status_code" class="cis-input w-full" required>
                        @foreach($statuses as $code => $meta)
                            <option value="{{ $code }}"
                                {{ old('status_code', $project->status_code) === $code ? 'selected' : '' }}>
                                {{ $meta['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="cis-label" for="category_id">Kategorie</label>
                    <x-cis-category-select type="project.category" name="category_id"
                                           :value="old('category_id', $project->category_id)" />
                </div>
            </div>

            {{-- Verantwortlicher --}}
            <div>
                <label class="cis-label">Verantwortliche(r)</label>
                @livewire('project.assignee-search', [
                    'assigneeType' => old('assignee_type', $project->assignee_type),
                    'assigneeId'   => old('assignee_id',   $project->assignee_id),
                ])
                @error('assignee_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Auftraggeber + Jahr + Fälligkeit --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="cis-label" for="client">Auftraggeber</label>
                    <input type="text" id="client" name="client" class="cis-input w-full"
                           value="{{ old('client', $project->client) }}">
                </div>
                <div>
                    <label class="cis-label" for="tender_year">Ausschreibungsjahr</label>
                    <input type="number" id="tender_year" name="tender_year" class="cis-input w-full"
                           value="{{ old('tender_year', $project->tender_year) }}" min="2000" max="2100">
                </div>
                <div>
                    <label class="cis-label" for="due_date">Fälligkeitsdatum</label>
                    <input type="date" id="due_date" name="due_date" class="cis-input w-full"
                           value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}">
                </div>
            </div>

            {{-- Mindestbestellwert --}}
            <div>
                <label class="cis-label" for="min_order_value">Mindestbestellwert je Anbieter</label>
                <div class="relative max-w-xs">
                    <input type="number" step="0.01" min="0" id="min_order_value" name="min_order_value"
                           class="cis-input w-full pr-8"
                           placeholder="Globaler Standard"
                           value="{{ old('min_order_value', $project->min_order_value) }}">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">€</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">Leer lassen, um den globalen Standard aus den Einstellungen zu verwenden.</p>
                @error('min_order_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('project') }}" class="btn btn-ghost">Zurück zur Liste</a>
            </div>
        </form>
    </div>
</div>
@endsection
