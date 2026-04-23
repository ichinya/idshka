# API_FLOWS

## 0. Р’С…РѕРґ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ РЅР° `idshka.ru` С‡РµСЂРµР· Socialite

### Redirect
```http
GET https://idshka.ru/auth/google/redirect
```

Laravel СЃРѕР·РґР°С‘С‚ `state` Рё РїРµСЂРµРЅР°РїСЂР°РІР»СЏРµС‚ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ Рє Google/VK/Yandex.

### Callback
```http
GET https://idshka.ru/auth/google/callback?code=<provider_code>&state=<state>
```

Laravel Socialite РїРѕР»СѓС‡Р°РµС‚ РґР°РЅРЅС‹Рµ provider user, СЃРѕР·РґР°С‘С‚/РѕР±РЅРѕРІР»СЏРµС‚ `users` Рё `social_accounts`, Р·Р°С‚РµРј СЃРѕР·РґР°С‘С‚ РѕР±С‹С‡РЅСѓСЋ Laravel session РґР»СЏ portal.

## 1. РџРѕРґРєР»СЋС‡РµРЅРёРµ СЃР°Р№С‚Р° `apishka.ru`

### РЎРѕР·РґР°С‚СЊ СЃР°Р№С‚
```http
POST https://idshka.ru/v1/sites
Authorization: Bearer <idshka_session_or_sanctum_token>
Content-Type: application/json

{
  "domain": "apishka.ru",
  "display_name": "Apishka"
}
```

РћС‚РІРµС‚:
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

### РџСЂРѕРІРµСЂРёС‚СЊ РґРѕРјРµРЅ
```http
POST https://idshka.ru/v1/sites/site_apishka/verify
Authorization: Bearer <idshka_session_or_sanctum_token>
Content-Type: application/json

{ "method": "dns_txt" }
```

## 2. Р’РєР»СЋС‡РёС‚СЊ API-only СЂРµР¶РёРј

```http
POST https://idshka.ru/v1/sites/site_apishka/modes/api_resource
Authorization: Bearer <owner_token_or_session>
Content-Type: application/json

{}
```

РћС‚РІРµС‚:
```json
{
  "site_id": "site_apishka",
  "mode": "api_resource",
  "enabled_at": "2026-04-22T22:00:00Z"
}
```

## 2.1 Р’РєР»СЋС‡РёС‚СЊ Web-client СЂРµР¶РёРј

```http
POST https://idshka.ru/v1/sites/site_apishka/modes/web_client
Authorization: Bearer <owner_token_or_session>
Content-Type: application/json

{}
```

РћС‚РІРµС‚:
```json
{
  "site_id": "site_apishka",
  "mode": "web_client",
  "enabled_at": "2026-04-22T22:00:00Z"
}
```

## 3. РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ СЃРѕР·РґР°С‘С‚ С‚РѕРєРµРЅ РґР»СЏ API `apishka.ru`

```http
POST https://idshka.ru/v1/user/api-tokens
Authorization: Bearer <idshka_session_or_sanctum_token>
Content-Type: application/json

{
  "site_id": "site_apishka",
  "audience": "apishka.ru",
  "label": "my local script",
  "scopes": ["orders.read"],
  "ttl_seconds": 3600
}
```

РћС‚РІРµС‚ РїРѕРєР°Р·С‹РІР°РµС‚СЃСЏ РѕРґРёРЅ СЂР°Р·:
```json
{
  "token_id": "tok_123",
  "token": "eyJhbGciOiJSUzI1NiIsImtpZCI6ImtleV8xIn0...",
  "expires_at": "2026-04-19T12:00:00Z"
}
```

## 4. РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РІС‹Р·С‹РІР°РµС‚ API `apishka.ru`

```http
GET https://api.apishka.ru/v1/orders
Authorization: Bearer <token_from_idshka>
```

РќР° gateway:
- РїСЂРѕРІРµСЂРёС‚СЊ JWT С‡РµСЂРµР· JWKS;
- РїСЂРѕРІРµСЂРёС‚СЊ `iss=https://idshka.ru`;
- РїСЂРѕРІРµСЂРёС‚СЊ `aud=apishka.ru`;
- РїСЂРѕРІРµСЂРёС‚СЊ `exp/nbf`;
- СѓРґР°Р»РёС‚СЊ РІС…РѕРґСЏС‰РёРµ `X-Idshka-*`;
- РґРѕР±Р°РІРёС‚СЊ РґРѕРІРµСЂРµРЅРЅС‹Р№ context РІ upstream.

Upstream РїРѕР»СѓС‡Р°РµС‚:
```http
X-Idshka-Authenticated: 1
X-Idshka-User-Id: usr_123
X-Idshka-Site-Id: site_apishka
X-Idshka-Audience: apishka.ru
X-Idshka-Scopes: orders.read
X-Idshka-Permissions: orders.read
X-Idshka-JTI: tok_123
```

## 5. Р’РєР»СЋС‡РёС‚СЊ Web login СЂРµР¶РёРј

```http
POST https://idshka.ru/v1/sites/site_apishka/oidc-client
Authorization: Bearer <owner_token_or_session>
Content-Type: application/json

{
  "client_name": "Apishka Web",
  "redirect_uris": ["https://apishka.ru/auth/idshka/callback"],
  "post_logout_redirect_uris": ["https://apishka.ru/"]
}
```

РћС‚РІРµС‚:
```json
{
  "client_id": "cli_apishka_web",
  "client_secret": "secret_shown_once",
  "issuer": "https://idshka.ru",
  "authorization_endpoint": "https://idshka.ru/oauth/authorize",
  "token_endpoint": "https://idshka.ru/oauth/token",
  "jwks_uri": "https://idshka.ru/oauth/jwks.json",
  "userinfo_endpoint": "https://idshka.ru/oauth/userinfo"
}
```

## 6. Web login: redirect РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ

```http
GET https://idshka.ru/oauth/authorize?response_type=code&client_id=cli_apishka_web&redirect_uri=https%3A%2F%2Fapishka.ru%2Fauth%2Fidshka%2Fcallback&scope=openid%20profile%20email&state=st_123&nonce=no_123&code_challenge=cc_123&code_challenge_method=S256
```

РџРѕСЃР»Рµ РІС…РѕРґР°:
```http
302 Location: https://apishka.ru/auth/idshka/callback?code=code_123&state=st_123
```

## 7. Web login: РѕР±РјРµРЅ code РЅР° tokens

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

РћС‚РІРµС‚:
```json
{
  "access_token": "...",
  "id_token": "...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

`apishka.ru` РїСЂРѕРІРµСЂСЏРµС‚ `id_token`, СЃРѕР·РґР°С‘С‚ СЃРІРѕСЋ Р»РѕРєР°Р»СЊРЅСѓСЋ session cookie Рё РїСѓСЃРєР°РµС‚ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ РІ web-РёРЅС‚РµСЂС„РµР№СЃ.
