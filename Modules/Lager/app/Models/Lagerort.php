<?php

namespace Modules\Lager\Models;

use App\Models\Project;
use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Ein Knoten im frei tiefen Lagerort-Baum (z.B. "Lager Nord" → "Raum 2" →
 * "Regal 1" → "Fach 2"). Bewusst kein eigenes "Typ"-Feld (Lager/Raum/Regal/
 * Fach) – die Baumtiefe selbst trägt die Bedeutung.
 */
class Lagerort extends Model
{
    use CisUuid, SoftDeletes;

    protected $table = 'lagerorte';

    protected $fillable = ['cis_row_id_parent', 'name', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'cis_row_id_parent', 'cis_row_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'cis_row_id_parent', 'cis_row_id')
            ->orderBy('sort_order')->orderBy('name');
    }

    public function projects()
    {
        return $this->belongsToMany(
            Project::class,
            'project_lagerort',
            'cis_row_id_lagerort',
            'cis_row_id_project',
            'cis_row_id',
            'cis_row_id'
        )->withTimestamps();
    }

    /** Eigene ID + alle Nachfahren-IDs (für den Verschieben-Zyklusschutz und Löschen). */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->cis_row_id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->selfAndDescendantIds());
        }

        return $ids;
    }

    /** Breadcrumb-Pfad, z.B. "Lager Nord › Raum 2 › Regal 1 › Fach 2". */
    public function path(string $separator = ' › '): string
    {
        $names = [];
        $node  = $this;
        while ($node) {
            array_unshift($names, $node->name);
            $node = $node->parent;
        }

        return implode($separator, $names);
    }

    /**
     * Nur die Wurzel-Lagerorte, mit rekursiv (im Speicher, ohne N+1-Queries)
     * gesetzter `children`-Relation – für die Baumansicht. Gleiches Muster wie
     * CisCategoryManager::treeForType().
     */
    public static function tree(): Collection
    {
        $all      = static::orderBy('sort_order')->orderBy('name')->get();
        $byParent = $all->groupBy('cis_row_id_parent');

        $build = function ($parentId) use (&$build, $byParent) {
            return $byParent->get($parentId, collect())
                ->map(function (self $node) use ($build) {
                    $node->setRelation('children', $build($node->cis_row_id));
                    return $node;
                })
                ->values();
        };

        return $build(null);
    }

    /** Alle Lagerorte als flache, tiefensortierte Liste mit ->depth (für Auswahllisten). */
    public static function flatTree(): Collection
    {
        $all = static::orderBy('sort_order')->orderBy('name')->get();
        // PHP-Arrays kennen keinen NULL-Schlüssel (wird zu '' – daher hier explizit
        // vereinheitlicht, damit der Wurzelaufruf walk('', 0) die oberste Ebene findet).
        $byParent = $all->groupBy(fn (self $n) => (string) $n->cis_row_id_parent);

        $walk = function (string $parentId, int $depth) use (&$walk, $byParent) {
            $result = collect();
            foreach ($byParent->get($parentId, collect()) as $node) {
                $node->depth = $depth;
                $result->push($node);
                $result = $result->concat($walk($node->cis_row_id, $depth + 1));
            }

            return $result;
        };

        return $walk('', 0);
    }
}
