<?php
namespace App\Http\Logic\CisAccess;

use App\Models\Area;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class CisAccess {

    private static $areas;
    private static $roles;
    private static $user;
    private static $userAreas;
    private static $authStatus = false;
    private static $init = false;

    public static function init() {
        if(self::$init) { return true; }
        if(Auth::check()) {
            self::$authStatus = true;
            self::$user = Auth::user();
            self::$areas = Area::all();
            self::$roles = Role::all();

            self::$userAreas = collect();

            foreach(self::$user->roles()->get() as $role) {
                foreach($role->areas()->get() as $area) {
                    if(!self::$userAreas->where('slug',$area->slug)->count()) {
                        self::$userAreas->add($area);
                    }
                }
            }
        }
    }

    public function hasAccess($area_slug) : bool {
        self::init();
        if(!self::$authStatus) {
            return false;
        }

        foreach(self::$userAreas as $area) {
            if($area->slug == $area_slug) {
                return true;
            }
        }
        return false;
    }

    public function area($area_slug) {
        self::init();
        if(!self::hasAccess($area_slug)) {
            return abort(403);
        }
    }
}
