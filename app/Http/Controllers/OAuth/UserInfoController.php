<?php

namespace App\Http\Controllers\OAuth;

use App\Contracts\Auth\OidcScopes;
use App\Domain\Issuer\Exceptions\IssuerFlowException;
use App\Domain\Issuer\Services\WebAccessTokenValidator;
use App\Http\Controllers\Concerns\ReturnsOAuthErrors;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class UserInfoController extends Controller
{
    use ReturnsOAuthErrors;

    public function __invoke(Request $request, WebAccessTokenValidator $tokenValidator): JsonResponse
    {
        $accessToken = (string) $request->bearerToken();

        if ($accessToken === '') {
            Log::warning('[oauth.userinfo] missing_bearer_token', [
                'request_id' => $request->attributes->get('request_id'),
                'has_authorization_header' => $request->headers->has('Authorization'),
                'has_http_authorization_server_value' => $request->server->has('HTTP_AUTHORIZATION'),
            ]);

            return $this->oauthError($request, 401, 'invalid_token', 'Missing bearer token.');
        }

        try {
            $validatedToken = $tokenValidator->validate($accessToken);
        } catch (IssuerFlowException $exception) {
            return $this->oauthError($request, $exception->httpStatus(), $exception->errorCode(), $exception->getMessage());
        }

        /** @var User|null $user */
        $user = User::query()->find($validatedToken->userId);

        if ($user === null) {
            return $this->oauthError($request, 401, 'invalid_token', 'Invalid access token.');
        }

        $payload = [
            'sub' => (string) $user->id,
        ];

        if (in_array(OidcScopes::EMAIL, $validatedToken->scopes, true)) {
            $payload['email'] = $user->email;
        }

        if (in_array(OidcScopes::PROFILE, $validatedToken->scopes, true)) {
            $payload['name'] = $user->name;
        }

        Log::info('[oauth.userinfo] completed', [
            'request_id' => $request->attributes->get('request_id'),
            'user_id' => $user->id,
            'client_id' => $validatedToken->clientId,
            'site_id' => $validatedToken->siteId,
            'jti' => $validatedToken->jti,
            'scopes_count' => count($validatedToken->scopes),
        ]);

        return response()->json($payload);
    }
}
