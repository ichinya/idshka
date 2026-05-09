<?php

namespace App\Http\Controllers\Portal;

use App\Domain\ApiResources\Exceptions\ApiResourceEligibilityException;
use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Issuer\Actions\IssueUserApiTokenAction;
use App\Domain\Issuer\Exceptions\IssuerFlowException;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\RevocationService;
use App\Domain\OidcClients\Actions\AddOidcRedirectUriAction;
use App\Domain\OidcClients\Actions\CreateOidcClientAction;
use App\Domain\OidcClients\Actions\RevokeOidcClientAction;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\Sites\Actions\CreateSiteAction;
use App\Domain\Sites\Actions\EnableSiteModeAction;
use App\Domain\Sites\Actions\VerifySiteDomainAction;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationMethod;
use App\Domain\Sites\Exceptions\SiteDomainConflictException;
use App\Domain\Sites\Exceptions\UnverifiedSiteException;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use App\Support\SafeLogContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

final class PortalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $userId = (int) $user->getAuthIdentifier();

        Log::info('[portal.dashboard] started', SafeLogContext::from([
            'user_id' => $userId,
        ]));

        $sites = Site::query()
            ->with(['modes', 'verifications'])
            ->where('owner_user_id', $userId)
            ->latest()
            ->get();

        $apiResourceSites = $sites
            ->filter(fn (Site $site): bool => $site->modes->contains('mode', SiteModeType::ApiResource->value))
            ->values();

        $apiTokens = ApiToken::query()
            ->with('site')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $clients = OidcClient::query()
            ->with(['site', 'redirectUris'])
            ->where('owner_user_id', $userId)
            ->latest()
            ->get();

        $auditEvents = AuditEvent::query()
            ->where('user_id', $userId)
            ->latest('occurred_at')
            ->limit(30)
            ->get();

        Log::info('[portal.dashboard] completed', SafeLogContext::from([
            'user_id' => $userId,
            'sites_count' => $sites->count(),
            'api_resource_sites_count' => $apiResourceSites->count(),
            'api_tokens_count' => $apiTokens->count(),
            'clients_count' => $clients->count(),
            'audit_events_count' => $auditEvents->count(),
        ]));

        return view('portal.dashboard', [
            'sites' => $sites,
            'apiResourceSites' => $apiResourceSites,
            'apiTokens' => $apiTokens,
            'clients' => $clients,
            'auditEvents' => $auditEvents,
        ]);
    }

    public function storeSite(Request $request, CreateSiteAction $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:120'],
        ]);

        Log::info('[portal.site.store] started', SafeLogContext::from([
            'user_id' => $user->getAuthIdentifier(),
            'domain' => $validated['domain'],
        ]));

        try {
            $site = $action->handle(
                ownerUserId: (int) $user->getAuthIdentifier(),
                domain: (string) $validated['domain'],
                displayName: $validated['display_name'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['domain' => $exception->getMessage()])->withInput();
        } catch (SiteDomainConflictException $exception) {
            return back()->withErrors(['domain' => $exception->getMessage()])->withInput();
        }

        Log::info('[portal.site.store] completed', SafeLogContext::from([
            'user_id' => $user->getAuthIdentifier(),
            'site_id' => $site->id,
        ]));

        return redirect()
            ->route('portal.developer.sites.show', $site)
            ->with('portal_notice', 'Site created');
    }

    public function verifySite(Request $request, Site $site, VerifySiteDomainAction $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        $this->authorizeSiteOwner($site, (int) $user->getAuthIdentifier());

        $validated = $request->validate([
            'method' => ['required', Rule::in([SiteVerificationMethod::DnsTxt->value, SiteVerificationMethod::File->value])],
        ]);

        $verification = $action->handle($site, SiteVerificationMethod::from((string) $validated['method']));
        $notice = 'Verification checked: '.$verification->status;

        if ($verification->last_error === 'verification_expired') {
            $notice .= '. New verification instructions generated.';
        }

        return redirect()
            ->route('portal.developer.sites.verification.show', $site)
            ->with('portal_notice', $notice);
    }

    public function enableSiteMode(
        Request $request,
        Site $site,
        string $mode,
        EnableSiteModeAction $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);
        $this->authorizeSiteOwner($site, (int) $user->getAuthIdentifier());

        $modeEnum = match ($mode) {
            SiteModeType::ApiResource->value => SiteModeType::ApiResource,
            SiteModeType::WebClient->value => SiteModeType::WebClient,
            default => null,
        };

        if ($modeEnum === null) {
            return back()->withErrors(['mode' => 'Unsupported site mode.']);
        }

        try {
            $action->handle($site, $modeEnum);
        } catch (UnverifiedSiteException $exception) {
            return back()->withErrors(['mode' => $exception->getMessage()]);
        }

        return redirect()
            ->route('portal.developer.sites.show', $site)
            ->with('portal_notice', $modeEnum === SiteModeType::ApiResource ? 'API resource enabled' : 'Web client enabled');
    }

    public function storeApiToken(Request $request, IssueUserApiTokenAction $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'site_id' => ['required', 'string', 'exists:sites,id'],
            'scopes' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'string', 'max:500'],
            'expires_mode' => ['nullable', Rule::in(['default', 'at', 'never'])],
            'expires_at' => ['exclude_unless:expires_mode,at', 'required', 'date', 'after:now'],
        ]);

        $scopes = $this->splitCsvWords($validated['scopes'] ?? '');
        $permissions = $this->splitCsvWords($validated['permissions'] ?? '');
        $expiresMode = (string) ($validated['expires_mode'] ?? 'default');
        $doesNotExpire = $expiresMode === 'never';
        $expiresAt = $expiresMode === 'at'
            ? CarbonImmutable::parse((string) $validated['expires_at'])->setTimezone('UTC')
            : null;

        try {
            $issued = $action->handle(
                userId: (int) $user->getAuthIdentifier(),
                siteId: (string) $validated['site_id'],
                requestedScopes: $scopes,
                requestedPermissions: $permissions,
                expiresAt: $expiresAt,
                doesNotExpire: $doesNotExpire,
            );
        } catch (ApiResourceEligibilityException|SigningKeyStateException|UnverifiedSiteException $exception) {
            return back()->withErrors(['api_token' => $exception->getMessage()])->withInput();
        } catch (Throwable $exception) {
            Log::error('[portal.api_token.store] unexpected_failure', SafeLogContext::from([
                'user_id' => $user->getAuthIdentifier(),
                'site_id' => $validated['site_id'],
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]));

            return back()->withErrors(['api_token' => 'Token issuance failed.'])->withInput();
        }

        return redirect()
            ->route('portal.account.tokens.index')
            ->with('portal_notice', 'Токен создан')
            ->with('issued_api_token', [
                'token' => $issued->rawToken,
                'token_id' => $issued->tokenId,
                'jti' => $issued->jti,
                'expires_at' => $issued->expiresAt?->toISOString() ?? 'Never',
            ]);
    }

    public function revokeApiToken(Request $request, ApiToken $apiToken, RevocationService $revocationService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $request->validate([
            'confirm' => ['required', 'in:revoke'],
        ]);

        if ($apiToken->user_id !== (int) $user->getAuthIdentifier()) {
            abort(404);
        }

        try {
            $revocationService->revokeForUser((int) $user->getAuthIdentifier(), $apiToken);
        } catch (IssuerFlowException $exception) {
            return back()->withErrors(['api_token' => $exception->getMessage()]);
        }

        return redirect()
            ->route('portal.account.tokens.index')
            ->with('portal_notice', 'Token revoked');
    }

    public function storeClient(Request $request, CreateOidcClientAction $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'site_id' => ['required', 'string', 'exists:sites,id'],
            'name' => ['required', 'string', 'max:120'],
            'redirect_uri' => ['required', 'url', 'starts_with:https://', 'max:2048', 'not_regex:/\*/'],
        ]);

        try {
            $issued = $action->handle(
                ownerUserId: (int) $user->getAuthIdentifier(),
                siteId: (string) $validated['site_id'],
                name: (string) $validated['name'],
                redirectUri: (string) $validated['redirect_uri'],
            );
        } catch (AuthorizationException) {
            abort(404);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['client' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('portal.developer.sites.credentials.index', $issued->client->site_id)
            ->with('portal_notice', 'Client created')
            ->with('issued_client', [
                'client_id' => $issued->client->client_id,
                'client_secret' => $issued->rawClientSecret,
            ]);
    }

    public function storeRedirectUri(
        Request $request,
        OidcClient $client,
        AddOidcRedirectUriAction $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'redirect_uri' => ['required', 'url', 'starts_with:https://', 'max:2048', 'not_regex:/\*/'],
        ]);

        try {
            $action->handle(
                ownerUserId: (int) $user->getAuthIdentifier(),
                client: $client,
                redirectUri: (string) $validated['redirect_uri'],
            );
        } catch (AuthorizationException) {
            abort(404);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['redirect_uri' => $exception->getMessage()]);
        }

        return redirect()
            ->route('portal.developer.sites.redirect-uris.index', $client->site_id)
            ->with('portal_notice', 'Redirect URI added');
    }

    public function revokeClient(Request $request, OidcClient $client, RevokeOidcClientAction $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $request->validate([
            'confirm' => ['required', 'in:revoke'],
        ]);

        try {
            $action->handle((int) $user->getAuthIdentifier(), $client);
        } catch (AuthorizationException) {
            abort(404);
        }

        return redirect()
            ->route('portal.developer.sites.credentials.index', $client->site_id)
            ->with('portal_notice', 'Client revoked');
    }

    private function authorizeSiteOwner(Site $site, int $userId): void
    {
        if ($site->owner_user_id !== $userId) {
            abort(404);
        }
    }

    /**
     * @return list<string>
     */
    private function splitCsvWords(string $value): array
    {
        $parts = preg_split('/[\s,]+/', $value) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $trimmed = trim($part);

            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return array_values(array_unique($normalized));
    }
}
