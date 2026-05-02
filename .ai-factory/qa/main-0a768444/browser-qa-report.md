# Browser QA Report: Portal Token and Client Management

Base URL: http://127.0.0.1:8090
Date: 2026-05-02T04:49:54.684Z

## Summary

- Total: 18
- PASS: 18
- FAIL: 0
- PARTIAL: 0

## Results

- PASS TC-002: Гость не может открыть портал — Guest /portal returned opaqueredirect/0 without showing the dashboard.
- PASS TC-001: Владелец открывает портал — Authenticated owner sees portal sections.
- PASS TC-003: Владелец создает сайт и видит инструкции верификации — Created site_01kqkgas6p9jpkbkw0dx3g0wqs and saw DNS/file instructions.
- PASS TC-004: Неверифицированный сайт не получает production modes — Both mode enable attempts left the pending site without production modes.
- PASS TC-005: Верифицированный владелец включает оба режима сайта — Both api_resource and web_client modes appear on dashboard.
- PASS TC-006: Владелец выпускает API token и видит raw token один раз — Raw token appeared once for token id 1 and disappeared after reload.
- PASS TC-007: Выпуск API token отклоняется для неeligible site — Pending and web-only sites did not receive raw API tokens.
- PASS TC-009: Владелец создает OIDC web client и видит secret один раз — Client client_01kqkgawehs03xwhkr8h20qx3x secret appeared once and disappeared after reload.
- PASS TC-010: Создание OIDC client отклоняется для неeligible site — Pending and api-only sites did not receive client secrets.
- PASS TC-011: Redirect URI принимает exact HTTPS и отклоняет unsafe values — Valid HTTPS redirect accepted; http, wildcard and malformed values rejected.
- PASS TC-012: Duplicate redirect URI не создает duplicate visible entries — Duplicate redirect did not add a row or audit event.
- PASS TC-017: Existing OIDC web login работает с portal-created client — Authorize, token exchange and userinfo succeeded for portal-created client.
- PASS TC-008: Revoke API token требует явного подтверждения — Empty/yes confirmations did not mutate state; exact revoke revoked the token.
- PASS TC-013: Revoke OIDC client требует явного подтверждения — Empty/delete confirmations did not mutate state; exact revoke revoked the client.
- PASS TC-016: Revoked OIDC client не принимает новый redirect URI — Revoked client rejected a new redirect URI.
- PASS TC-015: Audit table записывает lifecycle events без secrets — Portal showed lifecycle audit events and audit metadata did not contain raw token/client secret.
- PASS TC-014: Чужой владелец не может управлять ресурсами другого владельца — Owner B dashboard hid Owner A data and direct mutation attempts returned 404.
- PASS TC-018: Dashboard остается usable с длинными значениями — Desktop/mobile screenshots captured; page has no body horizontal overflow, controls stay inside viewport, and Audit is horizontally scrollable.

## Artifacts

- .ai-factory/qa/main-0a768444/browser-qa-results.json
- .ai-factory/qa/main-0a768444/browser-qa-portal-desktop.png
- .ai-factory/qa/main-0a768444/browser-qa-portal-mobile.png
