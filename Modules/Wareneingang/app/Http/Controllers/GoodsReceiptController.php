<?php

namespace Modules\Wareneingang\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Wareneingang\Models\GoodsReceiptParticipant;

class GoodsReceiptController extends Controller
{
    /**
     * Login-freie Kommissionieransicht. Der Token identifiziert einen einzelnen
     * Teilnehmer (Kommissionierer) und ist die einzige Zugriffskontrolle, daher
     * hier bewusst kein auth-Middleware in den Modul-Routen.
     */
    public function show(string $token)
    {
        $participant = GoodsReceiptParticipant::where('access_token', $token)->firstOrFail();

        return view('wareneingang::public', ['token' => $participant->access_token]);
    }
}
