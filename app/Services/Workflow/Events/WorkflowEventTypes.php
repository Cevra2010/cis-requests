<?php

namespace App\Services\Workflow\Events;

/** Registry der verfügbaren Ereignisarten. Core registriert seine in AppServiceProvider::boot(). */
class WorkflowEventTypes
{
    /** @var array<string, WorkflowEventType> */
    protected static array $types = [];

    public static function register(WorkflowEventType $type): void
    {
        static::$types[$type->key()] = $type;
    }

    public static function find(string $key): ?WorkflowEventType
    {
        return static::$types[$key] ?? null;
    }

    /** @return array<string, WorkflowEventType> */
    public static function all(): array
    {
        return static::$types;
    }

    /** @return array<string, WorkflowEventType> */
    public static function scheduled(): array
    {
        return array_filter(static::$types, fn (WorkflowEventType $t) => $t->scheduled());
    }
}
