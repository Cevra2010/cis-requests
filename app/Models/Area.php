<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory, CisUuid;

    public function hasChild() {
        if(Area::where('parent_slug',$this->slug)->count()) {
            return true;
        }
        return false;
    }
    public function getChilds() {
        if($childs = Area::where('parent_slug',$this->slug)->orderBy('slug')->get()) {
            return $childs;
        }
        return null;
    }

}
