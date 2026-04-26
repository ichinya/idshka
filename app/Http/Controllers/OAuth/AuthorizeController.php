<?php

namespace App\Http\Controllers\OAuth;

use App\Domain\Issuer\Services\AuthorizationCodeService;
use App\Domain\OidcClients\Exceptions\OidcClientException;
use App\Domain\OidcClients\Services\OidcClientResolver;
use App\Http\Controllers\Concerns\ReturnsOAuthErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\OAuth\AuthorizeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class AuthorizeController extends Controller
{
    use ReturnsOAuthErrors;

    public function __invoke(
        AuthorizeRequest $request,
        OidcClientResolver $clientResolver,
        AuthorizationCodeService $authorizationCodes,
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        if ($user === null) {
            return $this->oauthError($request, 401, 'authentication_required', 'Authentication required.');
        }

        Log::info('[oauth.authorize] started', [
            'request_id' => $request->attributes->get('request_id'),
            'user_id' => $user->getAuthIdentifier(),
            'client_id' => $request->string('client_id')->toString(),
        ]);

        try {
            $resolvedClient = $clientResolver->resolveForAuthorize(
                clientId: $request->string('client_id')->toString(),
                redirectUri: $request->string('redirect_uri')->toString(),
            );
            $issuedCode = $authorizationCodes->issue(
                resolvedClient: $resolvedClient,
                user: $user,
                scopes: $request->scopes(),
                nonce: $request->string('nonce')->toString(),
                codeChallenge: $request->string('code_challenge')->toString(),
                codeChallengeMethod: $request->string('code_challenge_method')->toString(),
            );
        } catch (OidcClientException $exception) {
            return $this->oauthError($request, $exception->httpStatus(), $exception->errorCode(), $exception->getMessage());
        }

        Log::info('[oauth.authorize] completed', [
            'request_id' => $request->attributes->get('request_id'),
            'user_id' => $user->getAuthIdentifier(),
            'client_id' => $resolvedClient->client->client_id,
            'site_id' => $resolvedClient->site->id,
            'authorization_code_id' => $issuedCode->record->id,
        ]);

        return redirect()->away($this->withAuthorizationResponseParameters($resolvedClient->redirectUri->redirect_uri, [
            'code' => $issuedCode->rawCode,
            'state' => $request->string('state')->toString(),
        ]));
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function withAuthorizationResponseParameters(string $redirectUri, array $parameters): string
    {
        $fragment = '';
        $baseUri = $redirectUri;
        $fragmentPosition = strpos($redirectUri, '#');

        if ($fragmentPosition !== false) {
            $fragment = substr($redirectUri, $fragmentPosition);
            $baseUri = substr($redirectUri, 0, $fragmentPosition);
        }

        $separator = str_contains($baseUri, '?') ? '&' : '?';

        return $baseUri.$separator.http_build_query($parameters).$fragment;
    }
}
