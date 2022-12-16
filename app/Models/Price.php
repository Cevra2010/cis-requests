<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Price extends Model
{
    use HasFactory ,CisUuid, SoftDeletes;

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

    public static function add($price, Product $product, ProductSource $source) {
        $newPrice = new Price();
        $newPrice->cis_row_id_product = $product->cis_row_id;
        $newPrice->cis_row_id_source = $source->cis_row_id;
        $newPrice->amount = self::fixAmountForDatabase($price);
        $newPrice->save();
        return true;
    }

    private static function fixAmountForDatabase($amount) {
        return str_replace(',','.',$amount);
    }
}
