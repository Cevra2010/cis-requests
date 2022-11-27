<?php

namespace App\Http\Middleware;

use App\Http\Logic\CisAccess\Facades\Access;
use Closure;
use Illuminate\Http\Request;

class Area
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $area_slug)
    {
        if(!Access::hasAccess($area_slug)) {
            return abort(403,'Kein Zugriff auf den Bereich "'.$area_slug.'". Bitte kontaktieren Sie den Administrator');
        }
        return $next($request);
    }
}
