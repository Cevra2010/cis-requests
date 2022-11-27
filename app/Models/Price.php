<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory ,CisUuid;

    protected $fillable = [
        'amount',
    ];

    public function amountForHumans() {
        if($this->amount == 0) {
            return '- preis gelöscht -';
        }
        if($this->amount) {
            return number_format($this->amount,2,",")." €";
        }
    }

    public function source() {
        return $this->hasOne(ProductSource::class,'cis_row_id','cis_row_id_source');
    }
}
