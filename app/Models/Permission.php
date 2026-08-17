<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $primaryKey = 'slug';

    protected $fillable = ['slug', 'label', 'module', 'description'];

    public function groupPermissions()
    {
        return $this->hasMany(GroupPermission::class, 'permission_slug', 'slug');
    }

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'permission_slug', 'slug');
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'permission_slug', 'slug');
    }
}
