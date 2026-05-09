# Implementation task

## 1. Portal shell

Create a reusable portal layout:

- `resources/views/layouts/portal.blade.php`
- `resources/views/portal/components/sidebar.blade.php`
- `resources/views/portal/components/topbar.blade.php`
- `resources/views/portal/components/page-header.blade.php`
- `resources/views/portal/components/empty-state.blade.php`
- `resources/views/portal/components/status-badge.blade.php`

Navigation groups:

- Account
- Developer
- Audit

The layout should be responsive and work with Blade + Tailwind. Alpine.js may be used for small interactions such as mobile menu, copied-to-clipboard state and collapsible panels.

## 2. Route split

Refactor portal routes into explicit groups:

```php
Route::middleware(['auth:web'])->prefix('portal')->name('portal.')->group(function () {
    Route::redirect('/', '/portal/account')->name('home');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountOverviewController::class, 'index'])->name('overview');
        Route::get('/social', [SocialAccountController::class, 'index'])->name('social.index');
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('/tokens', [UserTokenController::class, 'index'])->name('tokens.index');
    });

    Route::prefix('developer')->name('developer.')->group(function () {
        Route::get('/', [DeveloperOverviewController::class, 'index'])->name('overview');
        Route::resource('sites', SiteController::class)->except(['destroy']);
        Route::get('/sites/{site}/verification', [SiteVerificationController::class, 'show'])->name('sites.verification.show');
        Route::get('/sites/{site}/credentials', [SiteCredentialController::class, 'index'])->name('sites.credentials.index');
        Route::get('/sites/{site}/redirect-uris', [SiteRedirectUriController::class, 'index'])->name('sites.redirect-uris.index');
        Route::get('/sites/{site}/gateway', [IntegrationGuideController::class, 'gateway'])->name('sites.gateway');
        Route::get('/sites/{site}/web-login', [IntegrationGuideController::class, 'webLogin'])->name('sites.web-login');
    });

    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/{event}', [AuditEventController::class, 'show'])->name('show');
    });
});
```

Reuse current existing controllers/actions where practical. If current controller methods already implement mutations, keep route names compatible through redirects or aliases.

## 3. Account workspace

Pages:

- `portal/account/overview.blade.php`
- `portal/account/social/index.blade.php`
- `portal/account/sessions/index.blade.php`
- `portal/account/tokens/index.blade.php`

Content:

- Profile summary.
- Linked social providers: Google, VK, Yandex placeholders/buttons based on configured providers.
- Session/device list.
- User API tokens.
- Connected applications/sites the user has authorized.

## 4. Developer workspace

Pages:

- `portal/developer/overview.blade.php`
- `portal/developer/sites/index.blade.php`
- `portal/developer/sites/create.blade.php`
- `portal/developer/sites/show.blade.php`
- `portal/developer/sites/verification.blade.php`
- `portal/developer/sites/credentials.blade.php`
- `portal/developer/sites/redirect-uris.blade.php`
- `portal/developer/sites/gateway.blade.php`
- `portal/developer/sites/web-login.blade.php`

Content:

- Site list with mode badges: API-only, Web Login, Both.
- Domain verification status.
- Credentials page with one-time secret display warning.
- Redirect URI management.
- API-only integration guide: Authorization header, gateway contract, `X-Idshka-*` headers.
- Web Login integration guide: `/oauth/authorize`, `/oauth/token`, `/oauth/jwks.json`, `/oauth/userinfo`.

## 5. Audit workspace

Pages:

- `portal/audit/index.blade.php`
- `portal/audit/show.blade.php`

Features:

- Filters: date, event type, site, actor, severity.
- Event groups: account, social, token, site, credential, oauth, gateway, security.
- Detail page with metadata JSON rendered safely.
- Empty state for no results.

## 6. Visual polish

Use a calm SaaS-style UI:

- Left sidebar with product mark `idshka`.
- White/neutral cards, rounded borders, readable spacing.
- Strong status badges for verified/unverified, active/revoked, production/test.
- Copy buttons for snippets and IDs.
- Warning callouts for secrets and destructive actions.

## 7. Tests

Add/adjust tests:

- Portal route auth protection.
- Account pages render for authenticated user.
- Developer pages render only for owned sites where applicable.
- Audit page renders and filters do not error.
- Old `/portal` route redirects correctly.
- Existing OAuth issuer tests still pass.
