<?php

use Illuminate\Support\Facades\Route;
use Modules\Lager\Http\Controllers\LagerortController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/Lager', fn () => view('lager::index'))->name('lager.index');
    // Lagerorte werden nicht mehr auf einer eigenen Seite verwaltet, sondern als
    // gleichwertiger Tab direkt auf der "Ordnung"-Seite (siehe category-manager.blade.php).
    Route::get('/Lager/Lagerorte', fn () => redirect()->route('category.index', ['type' => 'lagerorte']))->name('lager.lagerorte');
    Route::get('/Lager/Buchen/{lagerort?}', fn (?string $lagerort = null) => view('lager::buchen', ['lagerortId' => $lagerort]))->name('lager.buchen');
    Route::get('/Lager/Lagerorte/{lagerort}/Etikett/PDF', [LagerortController::class, 'labelPdf'])->name('lager.lagerort.label.pdf');

    // Ziel des QR-Codes auf dem Etikett: mit der normalen Handykamera gescannt
    // (außerhalb der App, kein eingebauter Scanner) landet man direkt auf
    // "Ware buchen" für diesen Lagerort.
    Route::get('/Lager/Scan/{lagerort}', fn (string $lagerort) => redirect()->route('lager.buchen', $lagerort))->name('lager.scan');
});
