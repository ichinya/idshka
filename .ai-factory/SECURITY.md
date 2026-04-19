# SECURITY

## Основные угрозы
- Кража raw API token пользователя.
- Подделка `X-Idshka-*` заголовков клиентом.
- Неверный `aud` у токена.
- Утечка private signing key.
- Неверная привязка Socialite provider account к существующему пользователю.
- Open redirect в OAuth flow.
- Повторное использование authorization code.

## Базовые меры
- Short-lived JWT для API-only режима.
- JWKS + `kid` + ротация ключей.
- Strict redirect URI matching.
- CSRF/state/nonce/PKCE.
- Rate limits на auth/token endpoints.
- Audit events для всех security actions.
- Удаление всех входящих `X-Idshka-*` на gateway.
- Signed context при слабой сетевой границе.

## Секреты
Никогда не логировать:
- raw JWT/API tokens;
- Socialite provider access/refresh tokens;
- client secrets;
- private keys;
- authorization codes;
- refresh tokens.
