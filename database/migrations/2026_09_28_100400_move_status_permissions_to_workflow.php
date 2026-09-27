<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Übernimmt die bestehenden Freigabe-Rechte (project.status.set.* + das
 * Fixierungs-Override) 1:1 in workflow_approvers, damit sich nach dem Deploy
 * für niemanden etwas ändert (siehe Plan: "bestehende Freigabe-Rechte werden
 * übernommen, nicht auf 'offen für alle' zurückgesetzt"). Anschließend werden
 * die alten Permission-Slugs entfernt.
 */
return new class extends Migration
{
    private const CODE_SLUGS = [
        'ready_for_tender'     => 'project.status.set.ready_for_tender',
        'tender'               => 'project.status.set.tender',
        'ready_for_evaluation' => 'project.status.set.ready_for_evaluation',
        'evaluated'            => 'project.status.set.evaluated',
        'ordered'              => 'project.status.set.ordered',
        'orders_in_revision'   => 'project.status.set.orders_in_revision',
        'completed'            => 'project.status.set.completed',
    ];

    private const OVERRIDE_SLUG = 'project.tender.override_lock';

    private const TYPES = ['product', 'vehicle'];

    public function up(): void
    {
        $now     = now();
        $inserts = [];

        foreach (self::CODE_SLUGS as $code => $slug) {
            $principals = $this->resolvePrincipals($slug);

            foreach (self::TYPES as $type) {
                $statusId = DB::table('workflow_statuses')
                    ->where('project_type', $type)->where('code', $code)->value('cis_row_id');

                if (! $statusId) {
                    continue;
                }

                foreach ($principals as $p) {
                    $inserts[] = [
                        'cis_row_id'                 => (string) Str::uuid(),
                        'project_type'                => $type,
                        'scope'                       => 'transition',
                        'cis_row_id_workflow_status'  => $statusId,
                        'principal_type'              => $p['type'],
                        'principal_id'                => $p['id'],
                        'created_at'                  => $now,
                        'updated_at'                  => $now,
                    ];
                }
            }
        }

        $overridePrincipals = $this->resolvePrincipals(self::OVERRIDE_SLUG);
        foreach (self::TYPES as $type) {
            foreach ($overridePrincipals as $p) {
                $inserts[] = [
                    'cis_row_id'                 => (string) Str::uuid(),
                    'project_type'                => $type,
                    'scope'                       => 'lock_override',
                    'cis_row_id_workflow_status'  => null,
                    'principal_type'              => $p['type'],
                    'principal_id'                => $p['id'],
                    'created_at'                  => $now,
                    'updated_at'                  => $now,
                ];
            }
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            DB::table('workflow_approvers')->insert($chunk);
        }

        // Muss zusammen mit dem Entfernen der zugehörigen register()-Aufrufe in
        // AppServiceProvider passieren, sonst stellt der Boot-Sync sie wieder her.
        $allSlugs = array_merge(array_values(self::CODE_SLUGS), [self::OVERRIDE_SLUG]);
        DB::table('role_permissions')->whereIn('permission_slug', $allSlugs)->delete();
        DB::table('group_permissions')->whereIn('permission_slug', $allSlugs)->delete();
        DB::table('user_permissions')->whereIn('permission_slug', $allSlugs)->delete();
        DB::table('permissions')->whereIn('slug', $allSlugs)->delete();
    }

    /**
     * Löst die aktuell wirksamen Träger einer Permission auf. Normalfall (kein
     * explizites Verbot): globale Grants (Rolle/Gruppe/Nutzer) 1:1 übernehmen.
     * Existiert ein explizites User-Verbot (das eine Allow-Liste nicht
     * ausdrücken kann), wird für genau diesen Slug stattdessen der aktuell
     * wirksame Benutzerkreis als einzelne User-Freigaben eingefroren.
     *
     * @return array<int, array{type:string, id:string}>
     */
    private function resolvePrincipals(string $slug): array
    {
        $hasDeny = DB::table('user_permissions')
            ->where('permission_slug', $slug)->where('granted', false)->exists();

        if (! $hasDeny) {
            $principals = [];

            foreach (DB::table('role_permissions')->where('permission_slug', $slug)->where('granted', true)->whereNull('project_id')->pluck('role_id') as $id) {
                $principals[] = ['type' => 'role', 'id' => $id];
            }
            foreach (DB::table('group_permissions')->where('permission_slug', $slug)->where('granted', true)->whereNull('project_id')->pluck('group_id') as $id) {
                $principals[] = ['type' => 'group', 'id' => $id];
            }
            foreach (DB::table('user_permissions')->where('permission_slug', $slug)->where('granted', true)->whereNull('project_id')->pluck('user_id') as $id) {
                $principals[] = ['type' => 'user', 'id' => $id];
            }

            return $principals;
        }

        $denyUserIds = DB::table('user_permissions')
            ->where('permission_slug', $slug)->where('granted', false)->pluck('user_id')->all();

        $allowedUserIds = collect(DB::table('users')->whereNull('deleted_at')->pluck('cis_row_id'))
            ->filter(function ($userId) use ($slug, $denyUserIds) {
                if (in_array($userId, $denyUserIds, true)) {
                    return false;
                }
                if (DB::table('user_permissions')->where('user_id', $userId)->where('permission_slug', $slug)->where('granted', true)->exists()) {
                    return true;
                }
                $groupIds = DB::table('user_groups')->where('user_id', $userId)->pluck('group_id');
                if ($groupIds->isNotEmpty() && DB::table('group_permissions')->whereIn('group_id', $groupIds)->where('permission_slug', $slug)->where('granted', true)->exists()) {
                    return true;
                }
                $roleIds = DB::table('user_roles')->where('user_id', $userId)->pluck('role_id');
                if ($roleIds->isNotEmpty() && DB::table('role_permissions')->whereIn('role_id', $roleIds)->where('permission_slug', $slug)->where('granted', true)->exists()) {
                    return true;
                }
                return false;
            })
            ->values();

        if ($allowedUserIds->isNotEmpty()) {
            fwrite(STDOUT, "  Hinweis: \"{$slug}\" hatte ein explizites Verbot – {$allowedUserIds->count()} Benutzer wurden als einzelne Freigeber übernommen.\n");
        }

        return $allowedUserIds->map(fn ($id) => ['type' => 'user', 'id' => $id])->all();
    }

    public function down(): void
    {
        DB::table('workflow_approvers')->delete();

        $now    = now();
        $labels = [
            'project.tender.override_lock'            => 'Fixierte Ausschreibung bearbeiten',
            'project.status.set.ready_for_tender'      => 'Status „Bereit zur Ausschreibung“ setzen',
            'project.status.set.tender'                => 'Status „Ausschreibung“ setzen',
            'project.status.set.ready_for_evaluation'  => 'Status „Bereit zur Auswertung“ setzen',
            'project.status.set.evaluated'              => 'Status „Ausgewertet“ setzen',
            'project.status.set.ordered'                => 'Status „Bestellt“ setzen',
            'project.status.set.orders_in_revision'     => 'Status „Bestellungen in Revision“ setzen',
            'project.status.set.completed'              => 'Status „Abgeschlossen“ setzen',
        ];

        foreach ($labels as $slug => $label) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                ['label' => $label, 'module' => null, 'description' => null, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $adminRoleId = DB::table('roles')->where('name', 'Administrator')->whereNull('deleted_at')->value('cis_row_id');
        if ($adminRoleId) {
            foreach (array_keys($labels) as $slug) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $adminRoleId, 'permission_slug' => $slug, 'project_id' => null],
                    ['granted' => true]
                );
            }
        }
    }
};
