<?php

namespace Modules\Lager\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/** Typ eines Lagerorts (z.B. Halle "H", Regal "Reg") – frei pflegbar unter "Ordnung". */
class LagerortType extends Model
{
    use CisUuid;

    protected $table = 'lagerort_types';

    protected $fillable = ['name', 'abbreviation'];

    /** Standardtyp (ältester Eintrag); wird bei Bedarf angelegt, damit immer mindestens einer existiert. */
    public static function default(): self
    {
        return static::orderBy('created_at')->first()
            ?? static::create(['name' => 'Standard', 'abbreviation' => 'S']);
    }

    public function lagerorte()
    {
        return $this->hasMany(Lagerort::class, 'cis_row_id_type', 'cis_row_id');
    }
}
