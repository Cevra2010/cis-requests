<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, CisUuid;

    protected $fillable = [
        'name',
        'description',
        'created_at',
        'updated_at'
    ];

    public function getStatusText() {
        return collect(config("status"))->where('code',$this->status)->first()['name'];
    }

    public function getStatusColor() {
        return collect(config("status"))->where('code',$this->status)->first()['color'];
    }

    public function products() {
        return $this->belongsToMany(Product::class,'project_product','cis_row_id_project','cis_row_id_product','cis_row_id','cis_row_id');
    }
}
