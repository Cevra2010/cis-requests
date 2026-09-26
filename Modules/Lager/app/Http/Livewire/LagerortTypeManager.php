<?php

namespace Modules\Lager\Http\Livewire;

use Livewire\Component;
use Modules\Lager\Models\LagerortType;

/** Pflege der Lagerort-Typen (Name + Kürzel) unter "Ordnung". Mindestens ein Typ bleibt immer erhalten. */
class LagerortTypeManager extends Component
{
    public bool $showForm = false;
    public ?string $formId = null;
    public string $formName = '';
    public string $formAbbreviation = '';

    public ?string $error = null;

    public function openCreate(): void
    {
        $this->reset(['formId', 'formName', 'formAbbreviation', 'error']);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $type = LagerortType::findOrFail($id);
        $this->formId           = $type->cis_row_id;
        $this->formName         = $type->name;
        $this->formAbbreviation = $type->abbreviation;
        $this->error            = null;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'formName'         => 'required|string|max:100',
            'formAbbreviation' => 'required|string|max:20',
        ]);

        $data = ['name' => trim($this->formName), 'abbreviation' => trim($this->formAbbreviation)];

        $this->formId
            ? LagerortType::findOrFail($this->formId)->update($data)
            : LagerortType::create($data);

        $this->showForm = false;
    }

    public function delete(string $id): void
    {
        $type = LagerortType::findOrFail($id);

        if (LagerortType::count() <= 1) {
            $this->error = 'Es muss mindestens ein Typ vorhanden bleiben.';
            return;
        }

        $used = $type->lagerorte()->count();
        if ($used > 0) {
            $this->error = "„{$type->name}“ wird noch von {$used} Lagerort(en) verwendet und kann nicht gelöscht werden.";
            return;
        }

        $this->error = null;
        $type->delete();
    }

    public function render()
    {
        LagerortType::default();

        return view('lager::livewire.lagerort-type-manager', [
            'types' => LagerortType::withCount('lagerorte')->orderBy('name')->get(),
        ]);
    }
}
