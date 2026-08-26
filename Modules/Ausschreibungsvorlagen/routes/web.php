<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/Ausschreibungsvorlagen', fn () => view('ausschreibungsvorlagen::templates'))
        ->name('ausschreibungsvorlagen.index');
});
