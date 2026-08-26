<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Fixiert den Katalogpreis eines Produkts (Position oder Unterprodukt) für ein
 * Projekt zum Zeitpunkt der Ausschreibungs-Fixierung. Ändert sich der
 * Katalogpreis danach, bleiben bereits fixierte Projekte unverändert – siehe
 * Project::effectivePrice()/effectiveGroupPrice().
 */
class ProjectPriceSnapshot extends Model
{
    use CisUuid;

    protected $table = 'project_price_snapshots';

    protected $fillable = [
        'cis_row_id_project',
        'cis_row_id_product',
        'amount',
        'source_name',
        'frozen_at',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'frozen_at' => 'datetime',
    ];
}
