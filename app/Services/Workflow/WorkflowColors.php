<?php

namespace App\Services\Workflow;

/**
 * Literale Tailwind-Klassen je Farb-Key. Die DB (workflow_statuses.color)
 * speichert ausschließlich den Key, nie eine Klasse direkt – Tailwind scannt
 * nur app/**\/*.php nach Klassennamen, aus der DB gelesene Strings würden vom
 * Build sonst weggepurged.
 */
class WorkflowColors
{
    public const PALETTE = ['gray', 'blue', 'amber', 'orange', 'cyan', 'indigo', 'red', 'green', 'purple', 'teal', 'pink', 'lime'];

    protected const BADGE = [
        'gray'   => 'bg-gray-100 text-gray-600',
        'blue'   => 'bg-blue-100 text-blue-700',
        'amber'  => 'bg-amber-100 text-amber-700',
        'orange' => 'bg-orange-100 text-orange-700',
        'cyan'   => 'bg-cyan-100 text-cyan-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'red'    => 'bg-red-100 text-red-700',
        'green'  => 'bg-green-100 text-green-700',
        'purple' => 'bg-purple-100 text-purple-700',
        'teal'   => 'bg-teal-100 text-teal-700',
        'pink'   => 'bg-pink-100 text-pink-700',
        'lime'   => 'bg-lime-100 text-lime-700',
    ];

    protected const DOT = [
        'gray' => 'bg-gray-400', 'blue' => 'bg-blue-500', 'amber' => 'bg-amber-500',
        'orange' => 'bg-orange-500', 'cyan' => 'bg-cyan-500', 'indigo' => 'bg-indigo-500',
        'red' => 'bg-red-500', 'green' => 'bg-green-500', 'purple' => 'bg-purple-500',
        'teal' => 'bg-teal-500', 'pink' => 'bg-pink-500', 'lime' => 'bg-lime-500',
    ];

    protected const RING = [
        'gray' => 'ring-gray-100', 'blue' => 'ring-blue-100', 'amber' => 'ring-amber-100',
        'orange' => 'ring-orange-100', 'cyan' => 'ring-cyan-100', 'indigo' => 'ring-indigo-100',
        'red' => 'ring-red-100', 'green' => 'ring-green-100', 'purple' => 'ring-purple-100',
        'teal' => 'ring-teal-100', 'pink' => 'ring-pink-100', 'lime' => 'ring-lime-100',
    ];

    protected const TEXT = [
        'gray' => 'text-gray-500', 'blue' => 'text-blue-600', 'amber' => 'text-amber-600',
        'orange' => 'text-orange-600', 'cyan' => 'text-cyan-600', 'indigo' => 'text-indigo-600',
        'red' => 'text-red-600', 'green' => 'text-green-600', 'purple' => 'text-purple-600',
        'teal' => 'text-teal-600', 'pink' => 'text-pink-600', 'lime' => 'text-lime-600',
    ];

    public static function badge(?string $key): string
    {
        return self::BADGE[$key] ?? self::BADGE['gray'];
    }

    public static function dot(?string $key): string
    {
        return self::DOT[$key] ?? self::DOT['gray'];
    }

    public static function ring(?string $key): string
    {
        return self::RING[$key] ?? self::RING['gray'];
    }

    public static function text(?string $key): string
    {
        return self::TEXT[$key] ?? self::TEXT['gray'];
    }
}
