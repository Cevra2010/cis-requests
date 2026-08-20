<?php

use Illuminate\Support\Facades\Route;
use Modules\Export\Http\Controllers\TenderExportController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/Export/Templates', fn () => view('export::templates'))->name('export.templates');

    Route::get(
        '/Project/{project}/Export/Table/{template}/{format}',
        [TenderExportController::class, 'download']
    )->name('export.tender.table');
});
