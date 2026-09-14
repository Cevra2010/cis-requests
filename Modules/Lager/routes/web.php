<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/Lager', fn () => view('lager::index'))->name('lager.index');
    // Lagerorte werden nicht mehr auf einer eigenen Seite verwaltet, sondern als
    // gleichwertiger Tab direkt auf der "Ordnung"-Seite (siehe category-manager.blade.php).
    Route::get('/Lager/Lagerorte', fn () => redirect()->route('category.index', ['type' => 'lagerorte']))->name('lager.lagerorte');
    Route::get('/Lager/Buchen/{lagerort?}', fn (?string $lagerort = null) => view('lager::buchen', ['lagerortId' => $lagerort]))->name('lager.buchen');
});
