<?php

namespace App\Http\Controllers\OAuth;

use App\Domain\Issuer\Exceptions\IssuerFlowException;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Services\AuthorizationCodeService;
use App\Domain\Issuer\Services\PkceService;
use App\Domain\Issuer\Services\TokenIssuer;
use App\Domain\OidcClients\Exceptions\OidcClientException;
use App\Domain\OidcClients\Services\OidcClientResolver;
use App\Http\Controllers\Concerns\ReturnsOAuthErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\OAuth\TokenRequest;
use App\Support\SafeLogContext;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TokenController extends Controller
{
    use ReturnsOAuthErrors;

    public function __invoke(
        TokenRequest $request,
        OidcClientResolver $clientResolver,
        AuthorizationCodeService $authorizationCodes,
        PkceService $pkceService,
        TokenIssuer $tokenIssuer,
    ): JsonResponse {
        Log::info('[oauth.token] started', SafeLogContext::from([
            'request_id' => $request->attributes->get('request_id'),
            'client_id' => $request->string('client_id')->toString(),
        ]));

        try {
            $resolvedClient = $clientResolver->resolveForTokenExchange(
                clientId: $request->string('client_id')->toString(),
                clientSecret: $request->string('client_secret')->toString(),
                redirectUri: $request->string('redirect_uri')->toString(),
            );

            $authorizationCode = $authorizationCodes->consume(
                client: $resolvedClient->client,
                code: $request->string('code')->toString(),
                redirectUri: $request->string('redirect_uri')->toString(),
                codeVerifier: $request->string('code_verifier')->toString(),
                pkceService: $pkceService,
            );

            /** @var User $user */
            $user = User::query()->findOrFail($authorizationCode->user_id);
            $issuedTokens = $tokenIssuer->issueWebLoginTokens(
                userId: (int) $user->id,
                siteId: $authorizationCode->site_id,
                clientId: $resolvedClient->client->client_id,
                scopes: $authorizationCode->scopes,
                nonce: $authorizationCode->nonce,
                name: (string) $user->name,
                email: (string) $user->email,
            );
        } catch (OidcClientException|IssuerFlowException $exception) {
            return $this->oauthError($request, $exception->httpStatus(), $exception->errorCode(), $exception->getMessage());
        } catch (SigningKeyStateException $exception) {
            Log::error('[oauth.token] signing_key_failure', SafeLogContext::from([
                'request_id' => $request->attributes->get('request_id'),
                'client_id' => $request->string('client_id')->toString(),
                'error_code' => $exception->getMessage(),
            ]));

            return $this->oauthError($request, 503, $exception->getMessage(), 'Signing key is not ready for token issuance.');
        } catch (Throwable $exception) {
            Log::error('[oauth.token] unexpected_failure', SafeLogContext::from([
                'request_id' => $request->attributes->get('request_id'),
                'client_id' => $request->string('client_id')->toString(),
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]));

            return $this->oauthError($request, 500, 'token_issue_failed', 'Token issuance failed.');
        }

        Log::info('[oauth.token] completed', SafeLogContext::from([
            'request_id' => $request->attributes->get('request_id'),
            'client_id' => $resolvedClient->client->client_id,
            'site_id' => $authorizationCode->site_id,
            'user_id' => $authorizationCode->user_id,
            'authorization_code_id' => $authorizationCode->id,
            'access_token_jti' => $issuedTokens->accessTokenJti,
        ]));

        return response()->json([
            'access_token' => $issuedTokens->rawAccessToken,
            'id_token' => $issuedTokens->rawIdToken,
            'token_type' => 'Bearer',
            'expires_in' => max(1, (int) config('issuer.web_access_token_ttl_seconds', 600)),
            'scope' => implode(' ', $issuedTokens->scopes),
        ]);
    }
}
