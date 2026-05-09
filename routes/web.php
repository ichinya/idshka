<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\Auth\SocialiteCallbackController;
use App\Http\Controllers\Auth\SocialiteLinkRedirectController;
use App\Http\Controllers\Auth\SocialiteRedirectController;
use App\Http\Controllers\Auth\SocialiteUnlinkController;
use App\Http\Controllers\Portal\Account\AccountOverviewController;
use App\Http\Controllers\Portal\Account\SessionController;
use App\Http\Controllers\Portal\Account\SocialAccountController;
use App\Http\Controllers\Portal\Account\UserTokenController;
use App\Http\Controllers\Portal\Audit\AuditEventController;
use App\Http\Controllers\Portal\Audit\AuditLogController;
use App\Http\Controllers\Portal\Developer\DeveloperOverviewController;
use App\Http\Controllers\Portal\Developer\DeveloperSiteController;
use App\Http\Controllers\Portal\Developer\IntegrationGuideController;
use App\Http\Controllers\Portal\Developer\SiteCredentialController;
use App\Http\Controllers\Portal\Developer\SiteRedirectUriController;
use App\Http\Controllers\Portal\Developer\SiteVerificationController;
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
        Route::redirect('/', '/portal/account')->name('dashboard');

        Route::prefix('account')->name('account.')->group(function (): void {
            Route::get('/', AccountOverviewController::class)->name('overview');
            Route::get('/social', SocialAccountController::class)->name('social.index');
            Route::get('/sessions', SessionController::class)->name('sessions.index');
            Route::get('/tokens', UserTokenController::class)->name('tokens.index');
        });

        Route::prefix('developer')->name('developer.')->group(function (): void {
            Route::get('/', DeveloperOverviewController::class)->name('overview');
            Route::get('/sites', [DeveloperSiteController::class, 'index'])->name('sites.index');
            Route::get('/sites/create', [DeveloperSiteController::class, 'create'])->name('sites.create');
            Route::get('/sites/{site}', [DeveloperSiteController::class, 'show'])->name('sites.show');
            Route::get('/sites/{site}/verification', [SiteVerificationController::class, 'show'])->name('sites.verification.show');
            Route::get('/sites/{site}/credentials', [SiteCredentialController::class, 'index'])->name('sites.credentials.index');
            Route::get('/sites/{site}/redirect-uris', [SiteRedirectUriController::class, 'index'])->name('sites.redirect-uris.index');
            Route::get('/sites/{site}/gateway', [IntegrationGuideController::class, 'gateway'])->name('sites.gateway');
            Route::get('/sites/{site}/web-login', [IntegrationGuideController::class, 'webLogin'])->name('sites.web-login');
        });

        Route::prefix('audit')->name('audit.')->group(function (): void {
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('/{event}', [AuditEventController::class, 'show'])->name('show');
        });

        Route::post('/sites', [PortalController::class, 'storeSite'])
            ->middleware('throttle:portal-site-write')
            ->name('sites.store');
        Route::post('/sites/{site}/verify', [PortalController::class, 'verifySite'])
            ->middleware('throttle:portal-verification')
            ->name('sites.verify');
        Route::post('/sites/{site}/modes/{mode}', [PortalController::class, 'enableSiteMode'])
            ->middleware('throttle:portal-site-write')
            ->name('sites.modes.store');
        Route::post('/api-tokens', [PortalController::class, 'storeApiToken'])
            ->middleware('throttle:portal-credential-issue')
            ->name('api-tokens.store');
        Route::post('/api-tokens/{apiToken}/revoke', [PortalController::class, 'revokeApiToken'])
            ->middleware('throttle:portal-credential-revoke')
            ->name('api-tokens.revoke');
        Route::post('/clients', [PortalController::class, 'storeClient'])
            ->middleware('throttle:portal-client-write')
            ->name('clients.store');
        Route::post('/clients/{client}/redirect-uris', [PortalController::class, 'storeRedirectUri'])
            ->middleware('throttle:portal-redirect-uri-write')
            ->name('clients.redirect-uris.store');
        Route::post('/clients/{client}/revoke', [PortalController::class, 'revokeClient'])
            ->middleware('throttle:portal-credential-revoke')
            ->name('clients.revoke');
    });
