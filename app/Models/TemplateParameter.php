<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Wiederverwendbarer, kategorisierbarer Text-Baustein für die Fahrzeug-Konfiguration
 * (z.B. "Allrad" mit Unter-Parametern "Rampenwinkel", "Wattiefe"). Unabhängig von der
 * projektspezifischen product_parameters-Notiz an einzelnen Produkten.
 */
class TemplateParameter extends Model
{
    use CisUuid, SoftDeletes;

    protected $table = 'template_parameters';

    protected $fillable = [
        'name',
        'description',
        'cis_row_id_parent',
        'category_id',
        'sort_order',
    ];

    public function parent()
    {
        return $this->belongsTo(TemplateParameter::class, 'cis_row_id_parent', 'cis_row_id');
    }

    public function children()
    {
        return $this->hasMany(TemplateParameter::class, 'cis_row_id_parent', 'cis_row_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** IDs dieses Parameters und aller Nachfahren (für kaskadierendes Löschen). */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->cis_row_id];
        foreach (TemplateParameter::where('cis_row_id_parent', $this->cis_row_id)->get() as $child) {
            $ids = array_merge($ids, $child->selfAndDescendantIds());
        }
        return $ids;
    }

    /**
     * Dieser Parameter und alle Nachfahren, flach, tiefenorientiert
     * (für die Übernahme in einen Fahrzeug-Konfigurations-Block).
     */
    public function selfAndDescendantsFlat(int $depth = 0): array
    {
        $result = [['parameter' => $this, 'depth' => $depth]];
        foreach ($this->children as $child) {
            $result = array_merge($result, $child->selfAndDescendantsFlat($depth + 1));
        }
        return $result;
    }
}
