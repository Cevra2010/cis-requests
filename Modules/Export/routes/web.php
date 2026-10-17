<?php

use Illuminate\Support\Facades\Route;
use Modules\Export\Http\Controllers\DataTransferController;
use Modules\Export\Http\Controllers\TenderExportController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/Export/Templates', fn () => view('export::templates'))->name('export.templates');

    Route::get('/Export/Transfer', fn () => view('export::data-transfer'))->name('export.transfer');
    Route::get('/Export/Transfer/{template}/{format}', [DataTransferController::class, 'download'])->name('export.transfer.download');

    Route::get(
        '/Project/{project}/Export/Table/{template}/{format}',
        [TenderExportController::class, 'download']
    )->name('export.tender.table');
});
