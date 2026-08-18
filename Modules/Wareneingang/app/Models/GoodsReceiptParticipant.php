<?php

namespace Modules\Wareneingang\Models;

use App\Models\Traits\CisUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Ein eigener Kommissionier-Link. Mehrere Teilnehmer können denselben
 * GoodsReceipt (dieselbe Lieferung) parallel bearbeiten, jeder über seinen
 * eigenen Link – ohne Login. Ein Teilnehmer ist entweder mit einem
 * System-Benutzer verknüpft (cis_row_id_user) oder trägt nur einen Freitext-
 * Namen ("Unbekannter").
 */
class GoodsReceiptParticipant extends Model
{
    use CisUuid;

    protected $table = 'goods_receipt_participants';

    protected $fillable = [
        'cis_row_id_goods_receipt',
        'cis_row_id_user',
        'access_token',
        'name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function receipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'cis_row_id_goods_receipt', 'cis_row_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'cis_row_id_user', 'cis_row_id');
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class, 'cis_row_id_last_participant', 'cis_row_id');
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(40);
        } while (self::where('access_token', $token)->exists());

        return $token;
    }

    public function displayName(): string
    {
        return $this->user?->name() ?: ($this->name ?: 'Unbenannt');
    }

    /** Ein mit einem System-Benutzer verknüpfter Teilnehmer legt seinen Namen nicht selbst fest. */
    public function nameIsEditable(): bool
    {
        return $this->cis_row_id_user === null;
    }

    public function touchPresence(): void
    {
        $this->timestamps = false;
        $this->update(['last_seen_at' => now()]);
        $this->timestamps = true;
    }

    /** Innerhalb der letzten 20s gesehen = "gerade aktiv". */
    public function isActive(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subSeconds(20));
    }
}
