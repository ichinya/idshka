<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\Auth\SocialiteCallbackController;
use App\Http\Controllers\Auth\SocialiteLinkRedirectController;
use App\Http\Controllers\Auth\SocialiteRedirectController;
use App\Http\Controllers\Auth\SocialiteUnlinkController;
use App\Http\Controllers\Portal\PortalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.login')->name('register');
});

Route::middleware(['guest', 'throttle:auth-login'])->group(function (): void {
    Route::post('/register', RegisterUserController::class)->name('auth.register');
    Route::post('/login', LoginController::class)->name('auth.login');
});

Route::post('/logout', LogoutController::class)
    ->middleware('auth:web')
    ->name('auth.logout');

Route::get('/auth/{provider}/redirect', SocialiteRedirectController::class)
    ->middleware(['guest', 'throttle:auth-social'])
    ->name('auth.social.redirect');

Route::get('/auth/{provider}/link', SocialiteLinkRedirectController::class)
    ->middleware(['auth:web', 'throttle:auth-social'])
    ->name('auth.social.link.redirect');

Route::get('/auth/{provider}/callback', SocialiteCallbackController::class)
    ->middleware('throttle:auth-social')
    ->name('auth.social.callback');

Route::delete('/auth/{provider}/link', SocialiteUnlinkController::class)
    ->middleware(['auth:web', 'throttle:auth-social'])
    ->name('auth.social.unlink');

Route::middleware('auth:web')
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', [PortalController::class, 'index'])->name('dashboard');
        Route::post('/sites', [PortalController::class, 'storeSite'])->name('sites.store');
        Route::post('/sites/{site}/verify', [PortalController::class, 'verifySite'])->name('sites.verify');
        Route::post('/sites/{site}/modes/{mode}', [PortalController::class, 'enableSiteMode'])->name('sites.modes.store');
        Route::post('/api-tokens', [PortalController::class, 'storeApiToken'])->name('api-tokens.store');
        Route::post('/api-tokens/{apiToken}/revoke', [PortalController::class, 'revokeApiToken'])->name('api-tokens.revoke');
        Route::post('/clients', [PortalController::class, 'storeClient'])->name('clients.store');
        Route::post('/clients/{client}/redirect-uris', [PortalController::class, 'storeRedirectUri'])->name('clients.redirect-uris.store');
        Route::post('/clients/{client}/revoke', [PortalController::class, 'revokeClient'])->name('clients.revoke');
    });
