<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

/**
 * Cron-Trigger-URL – nur in der Entwicklungsumgebung (siehe available()): führt den Scheduler (schedule:run) einmal aus, genau wie der
 * Server-Cron es jede Minute tut. Nur aktiv, wenn in den Einstellungen freigeschaltet; geschützt durch ein zufälliges Token.
 */
class CronController extends Controller
{
    /** Die URL existiert nur in der Entwicklungsumgebung – in Produktion/Staging weder sichtbar noch aufrufbar. */
    public static function available(): bool
    {
        return app()->environment(['local', 'development', 'testing']);
    }

    public function run(string $token)
    {
        abort_unless(self::available(), 404);

        $expected = (string) Setting::get('cron_url_token', '');
        abort_unless(Setting::get('cron_url_enabled') === '1' && $expected !== '' && hash_equals($expected, $token), 404);

        app()->instance('cron.source', 'url');

        $started = microtime(true);
        Artisan::call('schedule:run');

        return response()->json([
            'status'      => 'ok',
            'ran_at'      => now()->toIso8601String(),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'output'      => trim(Artisan::output()),
        ]);
    }
}
