# API_FLOWS

## 1. Подключение сайта `apishka.ru`

### Создать сайт
```http
POST https://idshka.ru/v1/sites
Authorization: Bearer <idshka_session_access_token>
Content-Type: application/json

{
  "domain": "apishka.ru",
  "display_name": "Apishka"
}
```

Ответ:
```json
{
  "site_id": "site_apishka",
  "domain": "apishka.ru",
  "verified": false,
  "verification": {
    "dns_txt_name": "_idshka.apishka.ru",
    "dns_txt_value": "idshka-site-verification=ver_123",
    "file_url": "https://apishka.ru/.well-known/idshka-site-verification.txt",
    "file_body": "ver_123"
  }
}
```

### Проверить домен
```http
POST https://idshka.ru/v1/sites/site_apishka/verify
Authorization: Bearer <idshka_session_access_token>
Content-Type: application/json

{ "method": "dns_txt" }
```

## 2. Включить API-only режим

```http
POST https://idshka.ru/v1/sites/site_apishka/api-resource
Authorization: Bearer <owner_token>
Content-Type: application/json

{
  "audience": "apishka.ru",
  "scopes": ["orders.read", "orders.write"],
  "permissions": ["orders.read", "orders.write", "profile.read"]
}
```

Ответ:
```json
{
  "site_id": "site_apishka",
  "mode": "api_resource",
  "audience": "apishka.ru",
  "jwks_url": "https://idshka.ru/oauth/jwks.json"
}
```

## 3. Пользователь создаёт токен для API `apishka.ru`

```http
POST https://idshka.ru/v1/user/api-tokens
Authorization: Bearer <idshka_session_access_token>
Content-Type: application/json

{
  "site_id": "site_apishka",
  "audience": "apishka.ru",
  "label": "my local script",
  "scopes": ["orders.read"],
  "ttl_seconds": 3600
}
```

Ответ показывается один раз:
```json
{
  "token_id": "tok_123",
  "token": "eyJhbGciOiJSUzI1NiIsImtpZCI6ImtleV8xIn0...",
  "expires_at": "2026-04-19T12:00:00Z"
}
```

## 4. Пользователь вызывает API `apishka.ru`

```http
GET https://api.apishka.ru/v1/orders
Authorization: Bearer <token_from_idshka>
```

На gateway:
- проверить JWT через JWKS;
- проверить `iss=https://idshka.ru`;
- проверить `aud=apishka.ru`;
- проверить `exp/nbf`;
- удалить входящие `X-Idshka-*`;
- добавить доверенный context в upstream.

Upstream получает:
```http
X-Idshka-Authenticated: 1
X-Idshka-User-Id: usr_123
X-Idshka-Site-Id: site_apishka
X-Idshka-Audience: apishka.ru
X-Idshka-Scopes: orders.read
X-Idshka-Permissions: orders.read
X-Idshka-JTI: tok_123
```

## 5. Включить Web login режим

```http
POST https://idshka.ru/v1/sites/site_apishka/oidc-client
Authorization: Bearer <owner_token>
Content-Type: application/json

{
  "client_name": "Apishka Web",
  "redirect_uris": ["https://apishka.ru/auth/idshka/callback"],
  "post_logout_redirect_uris": ["https://apishka.ru/"]
}
```

Ответ:
```json
{
  "client_id": "cli_apishka_web",
  "client_secret": "secret_shown_once",
  "issuer": "https://idshka.ru",
  "authorization_endpoint": "https://idshka.ru/oauth/authorize",
  "token_endpoint": "https://idshka.ru/oauth/token",
  "jwks_uri": "https://idshka.ru/oauth/jwks.json"
}
```

## 6. Web login: redirect пользователя

```http
GET https://idshka.ru/oauth/authorize?response_type=code&client_id=cli_apishka_web&redirect_uri=https%3A%2F%2Fapishka.ru%2Fauth%2Fidshka%2Fcallback&scope=openid%20profile%20email&state=st_123&nonce=no_123&code_challenge=cc_123&code_challenge_method=S256
```

После входа:
```http
302 Location: https://apishka.ru/auth/idshka/callback?code=code_123&state=st_123
```

## 7. Web login: обмен code на tokens

```http
POST https://idshka.ru/oauth/token
Content-Type: application/x-www-form-urlencoded

 grant_type=authorization_code
 &code=code_123
 &redirect_uri=https%3A%2F%2Fapishka.ru%2Fauth%2Fidshka%2Fcallback
 &client_id=cli_apishka_web
 &client_secret=secret_shown_once
 &code_verifier=cv_123
```

Ответ:
```json
{
  "access_token": "...",
  "id_token": "...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

`apishka.ru` проверяет `id_token`, создаёт свою локальную session cookie и пускает пользователя в web-интерфейс.
