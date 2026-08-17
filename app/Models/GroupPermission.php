<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupPermission extends Model
{
    public $timestamps = false;
    protected $fillable = ['group_id', 'permission_slug', 'project_id', 'granted'];
    protected $casts    = ['granted' => 'boolean'];
}
