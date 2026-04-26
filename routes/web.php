<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\Auth\SocialiteCallbackController;
use App\Http\Controllers\Auth\SocialiteLinkRedirectController;
use App\Http\Controllers\Auth\SocialiteRedirectController;
use App\Http\Controllers\Auth\SocialiteUnlinkController;
use Illuminate\Support\Facades\Route;

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
