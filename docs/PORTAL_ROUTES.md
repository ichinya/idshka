# Proposed portal routes

## Root

```text
GET /portal
```

Redirect to `/portal/account` or render a workspace selector.

## Account

```text
GET /portal/account
GET /portal/account/social
POST /portal/account/social/{provider}/link
POST /portal/account/social/{provider}/unlink
GET /portal/account/sessions
POST /portal/account/sessions/{session}/revoke
GET /portal/account/tokens
POST /portal/account/tokens
POST /portal/account/tokens/{token}/revoke
```

## Developer

```text
GET /portal/developer
GET /portal/developer/sites
GET /portal/developer/sites/create
POST /portal/developer/sites
GET /portal/developer/sites/{site}
GET /portal/developer/sites/{site}/verification
POST /portal/developer/sites/{site}/verification/check
GET /portal/developer/sites/{site}/credentials
POST /portal/developer/sites/{site}/credentials
POST /portal/developer/sites/{site}/credentials/{credential}/rotate
GET /portal/developer/sites/{site}/redirect-uris
POST /portal/developer/sites/{site}/redirect-uris
DELETE /portal/developer/sites/{site}/redirect-uris/{redirectUri}
GET /portal/developer/sites/{site}/gateway
GET /portal/developer/sites/{site}/web-login
```

## Audit

```text
GET /portal/audit
GET /portal/audit/{event}
```

## OAuth issuer routes that must remain available

```text
GET /oauth/authorize
POST /oauth/token
GET /oauth/jwks.json
GET /oauth/userinfo
```

Recommended additions for a later plan:

```text
GET /.well-known/openid-configuration
POST /oauth/revoke
POST /oauth/introspect
```
