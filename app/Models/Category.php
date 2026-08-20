<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'description',
        'color',
        'sort_order',
        'module',
        'parent_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /** Alle Vorfahren, von der Wurzel bis zum direkten Elternteil. */
    public function ancestors(): array
    {
        $chain  = [];
        $cursor = $this->parent;
        while ($cursor) {
            array_unshift($chain, $cursor);
            $cursor = $cursor->parent;
        }
        return $chain;
    }

    /** IDs dieser Kategorie und aller Nachfahren (für kaskadierendes Löschen). */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->id];
        foreach (Category::where('parent_id', $this->id)->get() as $child) {
            $ids = array_merge($ids, $child->selfAndDescendantIds());
        }
        return $ids;
    }
}
