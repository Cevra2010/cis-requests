<?php

use Illuminate\Support\Facades\Route;
use Modules\Branding\Http\Controllers\BrandingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('brandings', BrandingController::class)->names('branding');
});
