# 07-security-hardening-and-ops

## Цель
Довести MVP до безопасной эксплуатации: key rotation, rate limits, observability, runbooks.

## Area
security, ops

## Что должно появиться
- key lifecycle next/active/retired
- rate limits
- denylist propagation
- metrics
- structured logs
- runbooks
- security checklist

## Зависимости
`03-token-issuer-and-jwks`, `04-api-resource-gateway-for-apishka`, `05-web-login-oidc-for-apishka`, `06-portal-token-and-client-management`

## Acceptance criteria
- key rotation без downtime
- revoked jti блокируется gateway
- auth metrics есть
- runbooks описывают key leak/JWKS outage/Redis outage

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
