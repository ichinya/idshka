# Area: issuer

- Только `idshka.ru` Laravel issuer подписывает токены.
- JWT должен содержать `iss`, `aud`, `sub`, `site_id`, `token_type`, `jti`, `iat`, `nbf`, `exp`.
- `kid` обязателен в JWT header.
- JWKS отдаёт только public key material.
- Private keys не логировать и не отдавать через API.
- Authorization code одноразовый и short-lived.
- PKCE обязателен для web login flow.
- Refresh token не вводить в MVP без отдельной threat model.
- Revoke прямого JWT делается через short TTL + optional denylist по `jti`.
