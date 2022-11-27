<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, CisUuid;

    protected $fillable = [
        'name',
    ];

    public function users() {
        return $this->belongsToMany(User::class,'user_role','cis_row_id_role','cis_row_id_user');
    }

    public function areas() {
        return $this->belongsToMany(Area::class,'area_role','cis_row_id_role','cis_row_id_area');
    }
}
