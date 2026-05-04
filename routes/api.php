<?php

use App\Http\Controllers\Api\Issuer\IssueUserApiTokenController;
use App\Http\Controllers\Api\Issuer\RevokeUserApiTokenController;
use App\Http\Controllers\Api\Sites\CreateSiteController;
use App\Http\Controllers\Api\Sites\EnableSiteModeController;
use App\Http\Controllers\Api\Sites\VerifySiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware(['web', 'auth:web', 'throttle:site-registry'])->group(function (): void {
        Route::post('/sites', CreateSiteController::class);
        Route::post('/sites/{site}/modes/{mode}', EnableSiteModeController::class)->middleware('can:manage,site');
    });

    Route::post('/sites/{site}/verify', VerifySiteController::class)
        ->middleware(['web', 'auth:web', 'throttle:site-verification', 'can:manage,site']);

    Route::middleware(['web', 'auth:web', 'throttle:token-issue'])->group(function (): void {
        Route::post('/user/api-tokens', IssueUserApiTokenController::class);
    });

    Route::middleware(['web', 'auth:web', 'throttle:token-revoke'])->group(function (): void {
        Route::post('/user/api-tokens/{id}/revoke', RevokeUserApiTokenController::class)->whereNumber('id');
    });
});
