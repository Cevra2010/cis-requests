<?php

use Illuminate\Support\Facades\Route;
use Modules\Wareneingang\Http\Controllers\GoodsReceiptController;

// Bewusst ohne 'auth'-Middleware: der Zugriff auf die Kommissionieransicht
// erfolgt ausschließlich über den geheimen Token im Link, kein Login nötig.
Route::get('/Wareneingang/{token}', [GoodsReceiptController::class, 'show'])
    ->name('wareneingang.public');
