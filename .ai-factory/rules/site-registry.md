# Area: site_registry

- Домен нормализовать: lowercase, punycode, без scheme/path/query.
- Один verified domain не может принадлежать двум владельцам без transfer flow.
- Верификация поддерживает минимум DNS TXT и файл в `/.well-known/`.
- Verification token должен иметь TTL и статус.
- Site modes включаются явно: `api_resource`, `web_client`.
- При смене домена все credentials/tokens нуждаются в review или revoke policy.
