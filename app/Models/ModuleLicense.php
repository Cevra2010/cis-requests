<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ModuleLicense extends Model
{
    use CisUuid;

    protected $fillable = [
        'module_name',
        'license_key',
        'licensee',
        'issued_at',
        'expires_at',
        'payload',
    ];

    protected $casts = [
        'issued_at'  => 'date',
        'expires_at' => 'date',
        'payload'    => 'array',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }
}
