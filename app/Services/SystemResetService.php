<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Destruktive Reset-Funktionen für die Einstellungen ("Gefahrenzone").
 *
 * Modul-eigene Daten (z.B. Branding, Wareneingang) und Modul-Lizenzen werden
 * von KEINER Stufe angefasst – Module bleiben von diesem Feature bewusst
 * unberührt.
 */
class SystemResetService
{
    /** Nur die Preistabelle leeren. */
    public function resetPrices(): array
    {
        return DB::transaction(function () {
            $count = DB::table('prices')->count();
            DB::table('prices')->delete();

            return ['Preise' => $count];
        });
    }

    /** Produktkatalog (Produkte, Preise, Beschreibungen, Parameter, Eltern/Kind-Beziehungen) leeren. */
    public function resetProductData(): array
    {
        return DB::transaction(function () {
            $summary = [
                'Produkte'               => DB::table('products')->count(),
                'Preise'                 => DB::table('prices')->count(),
                'Produktbeschreibungen'  => DB::table('product_descriptions')->count(),
                'Produktparameter'       => DB::table('product_parameters')->count(),
            ];

            DB::table('product_child')->delete();
            DB::table('prices')->delete();
            DB::table('product_descriptions')->delete();
            DB::table('product_parameters')->delete();
            DB::table('products')->delete();

            return $summary;
        });
    }

    /**
     * Alle Daten außer Benutzerkonten, Rollen und deren Berechtigungszuordnung
     * löschen. Modul-Daten bleiben unberührt.
     */
    public function resetSystem(): array
    {
        return DB::transaction(function () {
            $summary = $this->countCoreBusinessTables();

            $this->deleteCoreBusinessTables();

            return $summary;
        });
    }

    /**
     * Werkseinstellungen: den Zustand einer Neuinstallation herstellen.
     * Löscht wirklich alles außer Modul-Daten/-Lizenzen – inklusive
     * Benutzerkonten, Rollen und Berechtigungen. Danach existiert kein
     * Benutzer mehr, wodurch der Einrichtungs-Wizard automatisch greift.
     */
    public function factoryReset(): array
    {
        return DB::transaction(function () {
            $summary = $this->countCoreBusinessTables();
            $summary['Benutzerkonten'] = DB::table('users')->count();
            $summary['Rollen']         = DB::table('roles')->count();
            $summary['Gruppen']        = DB::table('groups')->count();

            $this->deleteCoreBusinessTables();

            DB::table('user_permissions')->delete();
            DB::table('user_roles')->delete();
            DB::table('role_permissions')->delete();
            DB::table('roles')->delete();
            DB::table('users')->delete();

            // Aktive Sessions/Tokens der jetzt gelöschten Benutzer sind wertlos.
            DB::table('sessions')->delete();
            DB::table('password_resets')->delete();
            DB::table('personal_access_tokens')->delete();

            return $summary;
        });
    }

    private function countCoreBusinessTables(): array
    {
        return [
            'Projekte'               => DB::table('projects')->count(),
            'Angebote'               => DB::table('offers')->count(),
            'Produkte'               => DB::table('products')->count(),
            'Preise'                 => DB::table('prices')->count(),
            'Produktquellen'         => DB::table('product_sources')->count(),
            'Kategorien'             => DB::table('categories')->count(),
        ];
    }

    private function deleteCoreBusinessTables(): void
    {
        // Erst abhängige/verknüpfende Tabellen, dann die Haupttabellen.
        DB::table('position_awards')->delete();
        DB::table('offer_items')->delete();
        DB::table('offers')->delete();

        DB::table('project_tender_blocks')->delete();
        DB::table('project_product')->delete();
        DB::table('project_last_touch')->delete();
        DB::table('projects')->delete();

        DB::table('product_child')->delete();
        DB::table('prices')->delete();
        DB::table('product_descriptions')->delete();
        DB::table('product_parameters')->delete();
        DB::table('products')->delete();
        DB::table('product_sources')->delete();

        DB::table('categories')->delete();

        DB::table('group_permissions')->delete();
        DB::table('user_groups')->delete();
        DB::table('groups')->delete();

        DB::table('settings')->delete();
    }
}
