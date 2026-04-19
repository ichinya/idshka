# Area: ops

## Rules
- Все сервисы имеют `/health` и `/ready`.
- Logs structured JSON.
- Correlation id: `X-Request-Id`.
- Metrics для auth success/failure, token issued/revoked, JWKS cache hit/miss, gateway deny reasons.
- Deploy должен поддерживать key rotation без downtime.
- Runbooks обязательны для key leak, bad deploy, JWKS outage, Redis outage.
- Docker Compose должен оставаться рабочим для локального smoke test.
