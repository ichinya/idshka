# laravel-web-client

Minimal Laravel web-client flow for logging in through `idshka.ru` with Authorization Code + PKCE.

The example intentionally uses only public HTTP/OAuth endpoints from `idshka.ru`. It must not import internal PHP classes from the issuer application.

## Client Settings

Register a verified `web_client` site in `idshka.ru` and create an OIDC client record:

- `client_id`: issued by `idshka.ru`
- `client_secret`: shown once by client-management UI or seed tooling
- `redirect_uri`: `https://example.test/auth/idshka/callback`
- scopes: `openid profile email`
- PKCE method: `S256`

Store secrets in environment variables:

```dotenv
IDSHKA_BASE_URL=https://idshka.ru
IDSHKA_CLIENT_ID=client_...
IDSHKA_CLIENT_SECRET=...
IDSHKA_REDIRECT_URI=https://example.test/auth/idshka/callback
```

Never log `client_secret`, authorization `code`, `id_token`, `access_token` or the PKCE verifier. Use request ids and hashes for diagnostics.

## Login Redirect

Generate a random `state`, `nonce` and PKCE verifier. Keep them in the local Laravel session until callback.

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/auth/idshka/redirect', function () {
    $state = Str::random(40);
    $nonce = Str::random(40);
    $verifier = Str::random(64);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    session([
        'idshka_oauth_state' => $state,
        'idshka_oauth_nonce' => $nonce,
        'idshka_pkce_verifier' => $verifier,
    ]);

    return redirect()->away(config('services.idshka.base_url').'/oauth/authorize?'.http_build_query([
        'response_type' => 'code',
        'client_id' => config('services.idshka.client_id'),
        'redirect_uri' => config('services.idshka.redirect_uri'),
        'scope' => 'openid profile email',
        'state' => $state,
        'nonce' => $nonce,
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]));
});
```

## Callback And Token Exchange

Validate `state`, then exchange the code with the stored PKCE verifier.

```php
Route::get('/auth/idshka/callback', function () {
    abort_unless(request('state') === session('idshka_oauth_state'), 419);

    $tokenResponse = Http::asForm()
        ->timeout(10)
        ->post(config('services.idshka.base_url').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.idshka.client_id'),
            'client_secret' => config('services.idshka.client_secret'),
            'code' => request('code'),
            'redirect_uri' => config('services.idshka.redirect_uri'),
            'code_verifier' => session('idshka_pkce_verifier'),
        ])
        ->throw()
        ->json();

    $idToken = $tokenResponse['id_token'];
    $accessToken = $tokenResponse['access_token'];

    // Validate id_token signature with GET /oauth/jwks.json before trusting claims.
    // Required checks: iss, aud=client_id, exp, nbf, iat, nonce, typ=JWT and token_type=id_token.

    $userinfo = Http::withToken($accessToken)
        ->acceptJson()
        ->timeout(10)
        ->get(config('services.idshka.base_url').'/oauth/userinfo')
        ->throw()
        ->json();

    // Create or update a local user and open a local Laravel session.
    $user = User::query()->updateOrCreate(
        ['idshka_sub' => $userinfo['sub']],
        [
            'email' => $userinfo['email'] ?? null,
            'name' => $userinfo['name'] ?? 'idshka user',
        ],
    );

    auth()->login($user, remember: true);

    session()->forget([
        'idshka_oauth_state',
        'idshka_oauth_nonce',
        'idshka_pkce_verifier',
    ]);

    return redirect('/dashboard');
});
```

## Public Endpoints

```http
GET  https://idshka.ru/oauth/authorize
POST https://idshka.ru/oauth/token
GET  https://idshka.ru/oauth/userinfo
GET  https://idshka.ru/oauth/jwks.json
```

`/oauth/token` supports only `grant_type=authorization_code` in this MVP. Refresh tokens are intentionally disabled; the web-client should use its own local session after callback.
