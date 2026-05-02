# Proposal: 07-portal-token-and-client-management

## Why

Site owners need a self-service portal to manage connected sites, verification instructions, API tokens, OIDC web clients, redirect URIs, revocation, and audit history without operator access to raw secrets.

## What Changes

- Add Blade/Tailwind portal surfaces for owned sites and verification guidance.
- Add portal flows for API token creation, listing, and revoke with one-time raw token display.
- Add portal flows for OIDC web client credentials and redirect URI management with one-time raw secret display.
- Add owner-visible audit history for site, token, client, redirect URI, and revoke lifecycle events.
- Require explicit confirmation for dangerous revoke actions.

## Scope

- Authenticated owner portal pages.
- Site creation and verification instruction UI.
- API token and OIDC client lifecycle UI.
- Redirect URI management UI.
- Owner-visible audit log UI.

## Approach

Review legacy plan notes and refine the OpenSpec change design.

## Legacy source

Migrated from:
- .ai-factory/plans/07-portal-token-and-client-management.md
- .ai-factory/plans/07-portal-token-and-client-management/task.md
- .ai-factory/plans/07-portal-token-and-client-management/context.md
- .ai-factory/plans/07-portal-token-and-client-management/rules.md
- .ai-factory/plans/07-portal-token-and-client-management/verify.md
- .ai-factory/plans/07-portal-token-and-client-management/status.yaml

## Legacy plan notes

# 07-portal-token-and-client-management

## Цель
Собрать self-service кабинет для сайтов, токенов, клиентов, revoke и audit.

## Area
portal, laravel, site_registry, issuer

## Что должно появиться
- Blade/Tailwind pages
- my sites UI
- verification instructions UI
- api token create/list/revoke UI
- web client credentials UI
- redirect URI management UI
- audit log UI

## Зависимости
02-user-auth-socialite, 03-site-registry-and-modes, 04-token-issuer-and-jwks

## Acceptance criteria
- владелец проходит flow создания сайта в UI
- secret/token показывается один раз
- revoke работает из UI
- audit events видны пользователю
- danger actions требуют подтверждения

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
