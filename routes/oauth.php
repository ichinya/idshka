<?php

use App\Http\Controllers\OAuth\PublicJwksController;
use Illuminate\Support\Facades\Route;

Route::prefix('oauth')->group(function (): void {
    Route::get('/jwks.json', PublicJwksController::class)->middleware('throttle:jwks-public');
});
