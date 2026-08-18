<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Frühere Wareneingänge hatten einen einzelnen Link direkt am GoodsReceipt
 * (Spalte access_token/checked_by_name). Diese Spalten werden durch eigene
 * Teilnehmer-Links ersetzt – bestehende Links bleiben als erster Teilnehmer
 * erhalten, damit bereits verteilte Links weiter funktionieren.
 */
return new class extends Migration
{
    public function up(): void
    {
        $receipts = DB::table('goods_receipts')->whereNotNull('access_token')->get();

        foreach ($receipts as $receipt) {
            DB::table('goods_receipt_participants')->insert([
                'cis_row_id'               => Uuid::uuid4()->toString(),
                'cis_row_id_goods_receipt' => $receipt->cis_row_id,
                'access_token'             => $receipt->access_token,
                'name'                     => $receipt->checked_by_name,
                'created_at'               => $receipt->created_at,
                'updated_at'               => $receipt->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // Datenmigration ist nicht sinnvoll rückgängig zu machen.
    }
};
