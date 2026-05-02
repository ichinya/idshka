import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import test from 'node:test';

import { exportJWK, generateKeyPair, SignJWT } from 'jose';

import { inspectToken, summarizeToken } from './tokenInspector.js';

test('summarizeToken reports non-expiring tokens without exp', () => {
  const summary = summarizeToken(
    { alg: 'RS256', kid: 'kid-1' },
    {
      iss: 'http://localhost:8080',
      aud: 'chat.ie0.ru',
      sub: '1',
      site_id: 'site_01test',
      token_type: 'user_api',
      scope: 'orders.read orders.write',
      permissions: ['orders.read'],
      jti: 'jti-1',
      iat: 1_700_000_000,
      nbf: 1_700_000_000,
    },
    new Date('2026-05-02T13:00:00.000Z'),
  );

  assert.equal(summary.does_not_expire, true);
  assert.equal(summary.expires_at, null);
  assert.deepEqual(summary.scopes, ['orders.read', 'orders.write']);
  assert.deepEqual(summary.permissions, ['orders.read']);
});

test('inspectToken verifies an idshka API token through JWKS', async () => {
  const { publicKey, privateKey } = await generateKeyPair('RS256');
  const publicJwk = await exportJWK(publicKey);
  publicJwk.kid = 'kid-1';
  publicJwk.alg = 'RS256';
  publicJwk.use = 'sig';

  const jwksServer = await listenJson({ keys: [publicJwk] });

  try {
    const now = Math.floor(Date.now() / 1000);
    const expiresAt = now + 3600;
    const token = await new SignJWT({
      site_id: 'site_01test',
      token_type: 'user_api',
      scope: 'orders.read',
      permissions: ['orders.read'],
    })
      .setProtectedHeader({ alg: 'RS256', kid: 'kid-1', typ: 'JWT' })
      .setIssuer('http://localhost:8080')
      .setAudience('chat.ie0.ru')
      .setSubject('1')
      .setJti('jti-1')
      .setIssuedAt(now)
      .setNotBefore(now)
      .setExpirationTime(expiresAt)
      .sign(privateKey);

    const result = await inspectToken(`Bearer ${token}`, {
      issuer: 'http://localhost:8080',
      jwksUrl: jwksServer.url,
      jwksTimeoutMs: 5000,
      jwksRetryCount: 1,
      clockToleranceSeconds: 5,
    }, {
      now: new Date(now * 1000),
    });

    assert.equal(result.ok, true);
    assert.equal(result.verification.status, 'verified');
    assert.equal(result.summary.audience[0], 'chat.ie0.ru');
    assert.equal(result.summary.expires_at, new Date(expiresAt * 1000).toISOString());
  } finally {
    await jwksServer.close();
  }
});

test('inspectToken retries once when the first JWKS request times out', async () => {
  const { publicKey, privateKey } = await generateKeyPair('RS256');
  const publicJwk = await exportJWK(publicKey);
  publicJwk.kid = 'kid-retry';
  publicJwk.alg = 'RS256';
  publicJwk.use = 'sig';

  const jwksServer = await listenJson({ keys: [publicJwk] }, { delayFirstResponseMs: 180 });

  try {
    const now = Math.floor(Date.now() / 1000);
    const token = await new SignJWT({
      site_id: 'site_01test',
      token_type: 'user_api',
      scope: 'orders.read',
      permissions: ['orders.read'],
    })
      .setProtectedHeader({ alg: 'RS256', kid: 'kid-retry', typ: 'JWT' })
      .setIssuer('http://localhost:8080')
      .setAudience('chat.ie0.ru')
      .setSubject('1')
      .setJti('jti-retry')
      .setIssuedAt(now)
      .setNotBefore(now)
      .setExpirationTime(now + 3600)
      .sign(privateKey);

    const result = await inspectToken(token, {
      issuer: 'http://localhost:8080',
      jwksUrl: jwksServer.url,
      jwksTimeoutMs: 80,
      jwksRetryCount: 1,
      clockToleranceSeconds: 5,
    });

    assert.equal(result.verification.status, 'verified');
    assert.equal(result.verification.attempts, 2);
    assert.equal(jwksServer.requestCount(), 2);
  } finally {
    await jwksServer.close();
  }
});

async function listenJson(payload, { delayFirstResponseMs = 0 } = {}) {
  let requestCount = 0;
  const server = createServer((request, response) => {
    requestCount += 1;
    const send = () => {
      response.writeHead(200, { 'content-type': 'application/json' });
      response.end(JSON.stringify(payload));
    };

    if (requestCount === 1 && delayFirstResponseMs > 0) {
      setTimeout(send, delayFirstResponseMs);

      return;
    }

    send();
  });

  await new Promise((resolve) => {
    server.listen(0, '127.0.0.1', resolve);
  });

  const { port } = server.address();

  return {
    url: `http://127.0.0.1:${port}/jwks.json`,
    requestCount: () => requestCount,
    close: () => new Promise((resolve, reject) => {
      server.close((error) => (error ? reject(error) : resolve()));
    }),
  };
}
