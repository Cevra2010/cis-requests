<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSource extends Model
{
    use HasFactory, CisUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'url',
        'contact_name',
        'contact_email',
        'contact_phone',
        'notes',
    ];

    public function prices()
    {
        return $this->hasMany(Price::class, 'cis_row_id_source', 'cis_row_id');
    }
}
