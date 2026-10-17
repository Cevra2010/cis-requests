<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/** Eine Ausführung einer geplanten Aufgabe (siehe App\Listeners\RecordCronRun). */
class CronRun extends Model
{
    use CisUuid;

    protected $fillable = ['task', 'source', 'status', 'duration_ms', 'message', 'ran_at'];

    protected $casts = ['ran_at' => 'datetime', 'duration_ms' => 'integer'];

    public const SOURCES = [
        'cron'   => 'Cron / Konsole',
        'url'    => 'Cron-URL',
        'manual' => 'Manuell (Einstellungen)',
    ];

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }
}
