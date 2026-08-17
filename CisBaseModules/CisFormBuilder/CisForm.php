<?php

namespace CisFoundation\CisFormBuilder;

use CisFoundation\CisHookManager\CisHooks;
use Illuminate\Support\Collection;

/**
 * CisForm – beschreibt ein Formular mit Feldern, Layout und Validierung.
 *
 * Verwendung:
 *   $form = CisFormBuilder::get('product.edit');
 *   $fields = $form->getFields();
 *   $rules  = $form->getValidationRules();
 *
 * Module erweitern ein Formular über:
 *   CisForm::extend('product.edit', function(CisForm $form) {
 *       $form->addField(
 *           CisField::make('min_quality_id', 'Mindestqualität')
 *               ->select()
 *               ->options(MinQuality::all())
 *               ->after('name')
 *               ->priority(25)
 *       );
 *   }, module: 'MinQualityModule');
 */
class CisForm
{
    public string  $name;
    public string  $method     = 'POST';
    public ?string $action     = null;
    public bool    $ajaxSubmit = false;
    public int     $columns    = 2;       // CSS grid columns (1–4)
    public ?string $template   = null;    // custom blade view

    protected Collection $fields;
    protected array      $sections = []; // ['slug' => 'Label']

    public function __construct(string $name)
    {
        $this->name   = $name;
        $this->fields = collect();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Form config
    // ────────────────────────────────────────────────────────────────────────

    public function method(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function columns(int $columns): static
    {
        $this->columns = max(1, min(4, $columns));
        return $this;
    }

    public function template(string $view): static
    {
        $this->template = $view;
        return $this;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Fields
    // ────────────────────────────────────────────────────────────────────────

    public function addField(CisField $field): static
    {
        $this->fields->push($field);
        return $this;
    }

    public function removeField(string $key): static
    {
        $this->fields = $this->fields
            ->filter(fn(CisField $f) => $f->key !== $key)
            ->values();
        return $this;
    }

    /**
     * Gibt alle sichtbaren Felder zurück – nach Hook-Integration,
     * Visibility-Filter, after()-Reihenfolge und Priority sortiert.
     */
    public function getFields(): Collection
    {
        $fields = CisHooks::applyFilter(
            'form.' . $this->name . '.fields',
            $this->fields->collect()
        );

        // Unsichtbare entfernen
        $fields = $fields->filter(fn(CisField $f) => $f->isVisible())->values();

        // Reihenfolge auflösen
        return $this->resolveFieldOrder($fields);
    }

    public function getValidationRules(): array
    {
        $rules = [];
        foreach ($this->getFields() as $field) {
            $fieldRules = $field->getRules();
            if (!empty($fieldRules)) {
                $rules[$field->key] = $fieldRules;
            }
        }
        return $rules;
    }

    public function getGridClass(): string
    {
        return match ($this->columns) {
            1 => 'grid grid-cols-1 gap-4',
            2 => 'grid grid-cols-1 md:grid-cols-2 gap-4',
            3 => 'grid grid-cols-1 md:grid-cols-3 gap-4',
            4 => 'grid grid-cols-1 md:grid-cols-4 gap-4',
            default => 'grid grid-cols-1 md:grid-cols-2 gap-4',
        };
    }

    // ────────────────────────────────────────────────────────────────────────
    // Static extension point (used by modules)
    // ────────────────────────────────────────────────────────────────────────

    public static function extend(string $name, callable $callback, ?string $module = null): void
    {
        CisHooks::addFilter(
            'form.' . $name . '.fields',
            function (Collection $fields) use ($name, $callback): Collection {
                $tempForm = new static($name);
                $callback($tempForm);
                $tempForm->fields->each(fn($f) => $fields->push($f));
                return $fields;
            },
            priority: 10,
            module:   $module
        );
    }

    // ────────────────────────────────────────────────────────────────────────
    // Internal: resolve after() directives + priority sort
    // ────────────────────────────────────────────────────────────────────────

    protected function resolveFieldOrder(Collection $fields): Collection
    {
        // First pass: sort by priority
        $sorted = $fields->sortBy(fn(CisField $f) => $f->priority)->values();

        // Second pass: resolve after() directives
        $withAfter    = $sorted->filter(fn(CisField $f) => $f->after !== null)->values();
        $withoutAfter = $sorted->filter(fn(CisField $f) => $f->after === null)->values();

        if ($withAfter->isEmpty()) {
            return $withoutAfter;
        }

        $result = $withoutAfter->all();

        foreach ($withAfter as $field) {
            $pos = array_search(
                $field->after,
                array_column($result, 'key')
            );

            if ($pos !== false) {
                array_splice($result, $pos + 1, 0, [$field]);
            } else {
                $result[] = $field;
            }
        }

        return collect($result);
    }
}
