# Verification

Run:

```bash
composer test
php artisan test
npm run build
```

Manual checks:

1. Open `/portal`; it should redirect or show the new portal shell.
2. Open `/portal/account`; personal identity cards should be visible.
3. Open `/portal/account/social`; provider placeholders/buttons should be visible without breaking if provider env vars are absent.
4. Open `/portal/developer`; developer overview should be visible.
5. Create or open a connected site; verification, credentials, redirect URI and integration guide pages should be reachable.
6. Open `/portal/audit`; audit table and filters should render.
7. Confirm old backend operations still work: token issue, JWKS, OAuth token exchange if tests exist.
8. Confirm CI is green after commit.
```
