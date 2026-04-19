# Area: security

## Rules
- Запрет логирования raw token, authorization code, client_secret, refresh_token, private key.
- Secrets хранить через env/secret manager, не в git.
- Password hash: Argon2id.
- JWT TTL по умолчанию короткий.
- Rate limit на login, token creation, token exchange, introspection.
- Audit event для каждого security-sensitive action.
- Threat model обновлять при добавлении нового flow.
- Все crypto decisions должны быть централизованы в Key Management.

## Reviews
- Любой PR, меняющий auth flow, требует security review checklist.
