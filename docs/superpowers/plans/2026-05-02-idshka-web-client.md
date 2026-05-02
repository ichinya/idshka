# idshka Web Client Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an HTTPS Dockerized Express demo client for login through idshka and raw userinfo display.

**Architecture:** The example is isolated under `examples/idshka-web-client`. OAuth logic, rendering, Express wiring, and HTTPS startup are split into focused modules so tests can exercise behavior without running Docker.

**Tech Stack:** Node.js 24, Express, express-session, built-in `node:test`, Docker Compose.

---

### Task 1: Test OAuth And Rendering Behavior

**Files:**
- Create: `examples/idshka-web-client/package.json`
- Create: `examples/idshka-web-client/src/oauth.test.js`
- Create: `examples/idshka-web-client/src/render.test.js`

- [x] **Step 1: Add a minimal Node test harness**

```json
{
  "name": "idshka-web-client",
  "private": true,
  "type": "module",
  "scripts": {
    "test": "node --test src/*.test.js",
    "start": "node src/server.js"
  }
}
```

- [x] **Step 2: Write failing tests**

Tests import `./oauth.js` and `./render.js`, which do not exist yet, so the first run must fail with module resolution errors.

- [x] **Step 3: Run red tests**

Run: `npm test --prefix examples/idshka-web-client`

Expected: FAIL because `src/oauth.js` and `src/render.js` are missing.

### Task 2: Implement OAuth And Rendering Units

**Files:**
- Create: `examples/idshka-web-client/src/oauth.js`
- Create: `examples/idshka-web-client/src/render.js`

- [x] **Step 1: Implement PKCE, authorize URL, state validation, token exchange, and userinfo fetch**

The OAuth module exposes `pkceChallengeForVerifier`, `buildAuthorizationRedirect`, `verifyReturnedState`, and `exchangeCodeForUserInfo`.

- [x] **Step 2: Implement escaped HTML rendering**

The rendering module exposes `renderHome` and `renderError`.

- [x] **Step 3: Run unit tests**

Run: `npm test --prefix examples/idshka-web-client`

Expected: PASS.

### Task 3: Implement Express App And HTTPS Docker Runtime

**Files:**
- Create: `examples/idshka-web-client/src/config.js`
- Create: `examples/idshka-web-client/src/app.js`
- Create: `examples/idshka-web-client/src/server.js`
- Create: `examples/idshka-web-client/Dockerfile`
- Create: `examples/idshka-web-client/docker-entrypoint.sh`
- Modify: `compose.yml`

- [x] **Step 1: Add configuration parsing**

Configuration reads issuer URLs, client credentials, redirect URI, session secret, HTTPS cert paths, and port from environment variables.

- [x] **Step 2: Wire Express routes**

Routes: `GET /`, `POST /auth/idshka/redirect`, `GET /auth/idshka/callback`, `POST /logout`, `GET /health`.

- [x] **Step 3: Add HTTPS startup and self-signed certificate entrypoint**

The container generates a localhost certificate when missing and starts `node src/server.js`.

- [x] **Step 4: Add Docker Compose service**

Service name: `idshka-web-client`, profile: `examples`, exposed URL: `https://localhost:8443`.

### Task 4: Document And Verify

**Files:**
- Create: `examples/idshka-web-client/README.md`

- [x] **Step 1: Document portal setup and environment variables**

Registered redirect URI: `https://localhost:8443/auth/idshka/callback`.

- [x] **Step 2: Verify**

Run:

```bash
npm test --prefix examples/idshka-web-client
docker compose config
docker compose --profile examples up -d --build idshka-web-client
```

Expected: tests pass, compose config is valid, HTTPS client container starts.
