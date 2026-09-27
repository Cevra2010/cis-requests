<?php

namespace App\Services\Workflow\Actions;

/** Registry der verfügbaren Maßnahmenarten. Core registriert 'email' in AppServiceProvider::boot(). */
class WorkflowActionTypes
{
    /** @var array<string, WorkflowActionType> */
    protected static array $types = [];

    public static function register(WorkflowActionType $type): void
    {
        static::$types[$type->key()] = $type;
    }

    public static function find(string $key): ?WorkflowActionType
    {
        return static::$types[$key] ?? null;
    }

    /** @return array<string, WorkflowActionType> */
    public static function all(): array
    {
        return static::$types;
    }
}
