<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, CisUuid, SoftDeletes;

    public function prices() {
        return $this->hasMany(Price::class,'cis_row_id_product');
    }

    public function price() {
        if($this->prices()->count()) {
            return $this->prices()->orderBy('created_at','DESC')->first();
        }
        return null;
    }

    public function priceForHumans()  {
        if(!$price = $this->price()) {
            return '- Preis nicht gesetzt -';
        }
        return number_format($price->amount,2,",")." €";
    }

    public function getGroupPrice() {
        $amount = 0;
        foreach($this->getChild() as $child) {
            if($child->price()) {
                $amount = $amount+$child->price()->amount;
            }
        }

        if($this->price() && $this->price()->amount != 0) {
            $amount = $amount + $this->price()->amount;
        }

        return $amount;
    }

    public function getGroupPriceForHumans() {
        return number_format($this->getGroupPrice(),2,",")." €";
    }

    public function parameters() {
        return $this->hasMany(ProductParameter::class,'cis_row_id_product');
    }

    public function hasParent() {
        if($this->parent) {
            return true;
        }
        return false;
    }

    public function getParent() {
        return Product::find($this->parent);
    }

    public function hasChild() {
        if(Product::where('parent',$this->cis_row_id)->count()) {
            return true;
        }
        return false;
    }

    public function getChild() {
        return Product::where('parent',$this->cis_row_id)->get();
    }

    public function description() {
        return ProductDescription::where('cis_row_id_product',$this->cis_row_id)->first();
    }

    public function newPrice($amount) {
        $amount = str_replace(",",".",$amount);
        $price = new Price();
        $price->cis_row_id_product = $this->cis_row_id;
        $price->amount = $amount;
        $this->updated_at = Carbon::now();
        $this->save();
        $price->save();
    }
}
