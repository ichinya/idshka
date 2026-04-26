# apishka-api

Минимальный upstream API consumer для gateway smoke.

`examples/apishka-api/nginx.conf` поднимается как internal Compose service без public port и возвращает только trusted context headers, которые выставил gateway:

- `X-Idshka-Authenticated`
- `X-Idshka-User-Id`
- `X-Idshka-Site-Id`
- `X-Idshka-Audience`
- `X-Idshka-Scopes`
- `X-Idshka-Permissions`
- `X-Idshka-JTI`
- `X-Idshka-Token-Exp`

Raw JWT не возвращается и не должен доходить до upstream.
