<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CisUuid, SoftDeletes;

    protected $fillable = ['firstname', 'lastname', 'email', 'password', 'avatar', 'birthdate', 'phone', 'bio'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthdate'         => 'date',
    ];

    // ────────────────────────────────────────────────────────────────────────
    // Relationen
    // ────────────────────────────────────────────────────────────────────────

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'user_groups', 'user_id', 'group_id', 'cis_row_id', 'cis_row_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id', 'cis_row_id', 'cis_row_id');
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id', 'cis_row_id');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Berechtigungsprüfung
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Prüft ob der Benutzer eine Berechtigung hat.
     *
     * Auflösungsreihenfolge:
     * 1. Explizites User-Verbot → false (gewinnt immer)
     * 2. Explizites User-Erlaubt (projekt-spezifisch) → true
     * 3. Explizites User-Erlaubt (global) → true
     * 4. Irgendeine Gruppe erlaubt es (projekt-spezifisch) → true
     * 5. Irgendeine Gruppe erlaubt es (global) → true
     * 6. Irgendeine Rolle erlaubt es (projekt-spezifisch) → true
     * 7. Irgendeine Rolle erlaubt es (global) → true
     * 8. → false
     */
    public function hasPermission(string $permission, ?string $projectId = null): bool
    {
        $userId = $this->cis_row_id;

        // 1. Explizites User-Verbot – gewinnt immer
        $anyDeny = DB::table('user_permissions')
            ->where('user_id', $userId)
            ->where('permission_slug', $permission)
            ->where('granted', false)
            ->exists();
        if ($anyDeny) {
            return false;
        }

        // 2. Explizites User-Erlaubt – projekt-spezifisch
        if ($projectId) {
            if (DB::table('user_permissions')
                ->where('user_id', $userId)
                ->where('permission_slug', $permission)
                ->where('project_id', $projectId)
                ->where('granted', true)
                ->exists()) {
                return true;
            }
        }

        // 3. Explizites User-Erlaubt – global
        if (DB::table('user_permissions')
            ->where('user_id', $userId)
            ->where('permission_slug', $permission)
            ->whereNull('project_id')
            ->where('granted', true)
            ->exists()) {
            return true;
        }

        $groupIds = $this->groups()->pluck('groups.cis_row_id');
        $roleIds  = $this->roles()->pluck('roles.cis_row_id');

        // 4 & 5. Gruppen
        if ($groupIds->isNotEmpty()) {
            $query = DB::table('group_permissions')
                ->whereIn('group_id', $groupIds)
                ->where('permission_slug', $permission)
                ->where('granted', true);

            if ($projectId) {
                if ((clone $query)->where('project_id', $projectId)->exists()) return true;
            }
            if ($query->whereNull('project_id')->exists()) return true;
        }

        // 6 & 7. Rollen
        if ($roleIds->isNotEmpty()) {
            $query = DB::table('role_permissions')
                ->whereIn('role_id', $roleIds)
                ->where('permission_slug', $permission)
                ->where('granted', true);

            if ($projectId) {
                if ((clone $query)->where('project_id', $projectId)->exists()) return true;
            }
            if ($query->whereNull('project_id')->exists()) return true;
        }

        return false;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Hilfsmethoden
    // ────────────────────────────────────────────────────────────────────────

    public function name(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function avatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        // Initials-Avatar als Fallback via UI Avatars
        $name = urlencode($this->firstname . '+' . $this->lastname);
        return "https://ui-avatars.com/api/?name={$name}&background=3b82f6&color=fff&size=128&bold=true";
    }
}
