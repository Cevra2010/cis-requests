<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, CisUuid, SoftDeletes;

    protected $fillable = ['name', 'description', 'color'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_groups', 'group_id', 'user_id', 'cis_row_id', 'cis_row_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'group_permissions',
            'group_id',
            'permission_slug',
            'cis_row_id',
            'slug'
        )->withPivot(['project_id', 'granted']);
    }

    /**
     * Prüft ob diese Gruppe eine Berechtigung hat (optional projekt-spezifisch).
     */
    public function hasPermission(string $slug, ?string $projectId = null): bool
    {
        return \DB::table('group_permissions')
            ->where('group_id', $this->cis_row_id)
            ->where('permission_slug', $slug)
            ->where('project_id', $projectId)
            ->where('granted', true)
            ->exists();
    }

    public function name(): string
    {
        return $this->name;
    }
}
