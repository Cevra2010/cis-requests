<?php

namespace Modules\Branding\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BrandingSetting extends Model
{
    use CisUuid;

    protected $fillable = [
        'logo_path',
        'header_line1',
        'header_line2',
        'footer_left',
        'footer_right',
        'accent_color',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'accent_color' => '#4F46E5',
        ]);
    }

    public function logoUrl(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return '/storage/' . ltrim($this->logo_path, '/');
    }
}
