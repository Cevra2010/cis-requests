<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

class ProjectProduct extends Model
{
    use CisUuid;

    protected $table = 'project_product';

    protected $fillable = [
        'cis_row_id_project',
        'cis_row_id_product',
        'product_count',
        'note',
        'sort_order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'cis_row_id_project', 'cis_row_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'cis_row_id_product', 'cis_row_id');
    }

    public function award()
    {
        return $this->hasOne(PositionAward::class, 'cis_row_id_project_product', 'cis_row_id');
    }

    public function offerItems()
    {
        return $this->hasMany(OfferItem::class, 'cis_row_id_project_product', 'cis_row_id');
    }
}
