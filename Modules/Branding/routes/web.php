<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/branding/settings', fn() => view('branding::pages.settings'))
        ->name('branding.settings');
});
