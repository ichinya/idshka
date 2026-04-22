<?php

use App\Http\Controllers\Api\Sites\CreateSiteController;
use App\Http\Controllers\Api\Sites\EnableSiteModeController;
use App\Http\Controllers\Api\Sites\VerifySiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth')->group(function (): void {
        Route::post('/sites', CreateSiteController::class);
        Route::post('/sites/{site}/verify', VerifySiteController::class);
        Route::post('/sites/{site}/modes/{mode}', EnableSiteModeController::class);
    });
});
