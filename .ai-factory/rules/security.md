# Area: security

- Raw tokens, private keys, client secrets, provider tokens не логируются.
- Rate limit обязателен на login, callbacks, token issue, token endpoint, introspection.
- CSRF включён для web routes.
- API endpoints используют явные guards и policies.
- HMAC подписи проверять через constant-time comparison.
- Использовать secure cookies: httpOnly, SameSite, Secure на production.
- Audit events не должны содержать секреты.
