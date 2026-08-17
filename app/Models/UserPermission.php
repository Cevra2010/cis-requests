<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'permission_slug', 'project_id', 'granted'];
    protected $casts    = ['granted' => 'boolean'];
}
