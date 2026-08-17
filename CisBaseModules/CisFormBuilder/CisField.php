<?php

namespace CisFoundation\CisFormBuilder;

use Illuminate\Support\Collection;

/**
 * CisField – ein einzelnes Formularfeld.
 *
 * Fluent API:
 *   CisField::make('name', 'Name')
 *       ->type('text')
 *       ->required()
 *       ->placeholder('Max Mustermann')
 *       ->visibleWhen(fn() => auth()->user()->isAdmin())
 *       ->help('Ihr vollständiger Name')
 *       ->after('email');
 */
class CisField
{
    public string  $key;
    public string  $label;
    public string  $type        = 'text';
    public ?string $placeholder = null;
    public ?string $help        = null;
    public bool    $required    = false;
    public bool    $disabled    = false;
    public bool    $readonly    = false;
    public ?string $after       = null;
    public int     $priority    = 10;
    public ?string $width       = null;   // e.g. 'col-span-2', 'col-span-full'
    public mixed   $default     = null;

    /** Options for select / radio / checkbox-group: [['value' => ..., 'label' => ...]] */
    protected array $options = [];

    /** Custom render closure: fn(mixed $model) => string */
    protected ?\Closure $renderCallback = null;

    /** Visibility closure: fn() => bool */
    protected ?\Closure $visibleWhen = null;

    /** Validation rules (Laravel-style) */
    protected array $rules = [];

    /** Extra HTML attributes */
    protected array $attributes = [];

    // ────────────────────────────────────────────────────────────────────────
    // Factory
    // ────────────────────────────────────────────────────────────────────────

    public static function make(string $key, string $label): static
    {
        $field        = new static();
        $field->key   = $key;
        $field->label = $label;
        return $field;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Type shortcuts
    // ────────────────────────────────────────────────────────────────────────

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function text(): static       { return $this->type('text'); }
    public function email(): static      { return $this->type('email'); }
    public function password(): static   { return $this->type('password'); }
    public function number(): static     { return $this->type('number'); }
    public function url(): static        { return $this->type('url'); }
    public function date(): static       { return $this->type('date'); }
    public function textarea(): static   { return $this->type('textarea'); }
    public function checkbox(): static   { return $this->type('checkbox'); }
    public function toggle(): static     { return $this->type('toggle'); }
    public function hidden(): static     { return $this->type('hidden'); }

    public function select(array $options = []): static
    {
        $this->type    = 'select';
        $this->options = $this->normalizeOptions($options);
        return $this;
    }

    public function radio(array $options = []): static
    {
        $this->type    = 'radio';
        $this->options = $this->normalizeOptions($options);
        return $this;
    }

    /**
     * Optionen als assoziatives Array ['value' => 'label'] oder als
     * Collection von Eloquent-Modellen mit id/name-Attributen übergeben.
     *
     * @param array|Collection $options
     */
    public function options(array|Collection $options): static
    {
        if ($options instanceof Collection) {
            $this->options = $options->map(fn($o) => [
                'value' => $o->id,
                'label' => $o->name,
            ])->all();
        } else {
            $this->options = $this->normalizeOptions($options);
        }
        return $this;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Fluent setters
    // ────────────────────────────────────────────────────────────────────────

    public function required(bool $value = true): static
    {
        $this->required = $value;
        if ($value) {
            $this->rules[] = 'required';
        }
        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->disabled = $value;
        return $this;
    }

    public function readonly(bool $value = true): static
    {
        $this->readonly = $value;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function help(string $help): static
    {
        $this->help = $help;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;
        return $this;
    }

    public function after(string $fieldKey): static
    {
        $this->after = $fieldKey;
        return $this;
    }

    public function priority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /** Tailwind grid-span class, e.g. 'col-span-full', 'col-span-2' */
    public function width(string $class): static
    {
        $this->width = $class;
        return $this;
    }

    public function fullWidth(): static
    {
        return $this->width('col-span-full');
    }

    public function rules(array|string $rules): static
    {
        $parsed = is_string($rules) ? explode('|', $rules) : $rules;
        $this->rules = array_unique(array_merge($this->rules, $parsed));
        return $this;
    }

    public function attr(string $name, string $value): static
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function visibleWhen(\Closure $condition): static
    {
        $this->visibleWhen = $condition;
        return $this;
    }

    /**
     * Komplett eigenes Render-Template per Closure.
     * fn(mixed $model, CisField $field) => string (HTML)
     */
    public function render(\Closure $callback): static
    {
        $this->renderCallback = $callback;
        return $this;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Getter
    // ────────────────────────────────────────────────────────────────────────

    public function getKey(): string    { return $this->key; }
    public function getLabel(): string  { return $this->label; }
    public function getType(): string   { return $this->type; }
    public function getOptions(): array { return $this->options; }
    public function getRules(): array   { return $this->rules; }
    public function getAttributes(): array { return $this->attributes; }

    public function isVisible(): bool
    {
        if ($this->visibleWhen === null) {
            return true;
        }
        return (bool) ($this->visibleWhen)();
    }

    public function hasCustomRenderer(): bool
    {
        return $this->renderCallback !== null;
    }

    public function renderCustom(mixed $model): string
    {
        return ($this->renderCallback)($model, $this);
    }

    /** HTML attributes string for blade templates */
    public function getAttributeString(): string
    {
        $parts = [];
        foreach ($this->attributes as $name => $value) {
            $parts[] = e($name) . '="' . e($value) . '"';
        }
        return implode(' ', $parts);
    }

    /** Returns the value from a model/array for this field */
    public function getValue(mixed $model): mixed
    {
        if (is_array($model)) {
            return $model[$this->key] ?? $this->default;
        }
        if (is_object($model)) {
            return $model->{$this->key} ?? $this->default;
        }
        return $this->default;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ────────────────────────────────────────────────────────────────────────

    protected function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $key => $value) {
            if (is_array($value) && isset($value['value'])) {
                $normalized[] = $value;
            } else {
                $normalized[] = ['value' => $key, 'label' => $value];
            }
        }
        return $normalized;
    }
}
