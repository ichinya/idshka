<?php

use App\Http\Controllers\OAuth\AuthorizeController;
use App\Http\Controllers\OAuth\PublicJwksController;
use App\Http\Controllers\OAuth\TokenController;
use App\Http\Controllers\OAuth\UserInfoController;
use Illuminate\Support\Facades\Route;

Route::prefix('oauth')->group(function (): void {
    Route::get('/authorize', AuthorizeController::class)->middleware(['web', 'throttle.oauth-authorize', 'auth:web']);
    Route::post('/token', TokenController::class)->middleware('throttle:oauth-token');
    Route::get('/userinfo', UserInfoController::class)->middleware('throttle:oauth-userinfo');
    Route::get('/jwks.json', PublicJwksController::class)->middleware('throttle:jwks-public');
});
