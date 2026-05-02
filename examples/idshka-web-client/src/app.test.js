import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import test from 'node:test';

import { createApp } from './app.js';

const config = {
  publicIssuerUrl: 'https://idshka.test',
  internalIssuerUrl: 'http://nginx',
  clientId: 'client_01test',
  clientSecret: 'secret_test',
  redirectUri: 'https://localhost:8443/auth/idshka/callback',
  scopes: 'openid profile email',
  sessionSecret: 'test-session-secret',
  secureCookies: false,
};

test('createApp exposes health endpoint and starts OAuth redirect with a session cookie', async () => {
  const app = createApp({ config });

  const health = await request(app, { path: '/health' });
  assert.equal(health.status, 200);
  assert.deepEqual(JSON.parse(health.body), { ok: true });

  const redirect = await request(app, {
    method: 'POST',
    path: '/auth/idshka/redirect',
  });

  assert.equal(redirect.status, 302);
  assert.match(redirect.headers.get('location'), /^https:\/\/idshka\.test\/oauth\/authorize\?/);
  assert.match(redirect.headers.get('set-cookie'), /idshka_demo_session=/);
});

async function request(app, { method = 'GET', path = '/' }) {
  const server = createServer(app);

  await new Promise((resolve) => {
    server.listen(0, '127.0.0.1', resolve);
  });

  try {
    const { port } = server.address();
    const response = await fetch(`http://127.0.0.1:${port}${path}`, {
      method,
      redirect: 'manual',
    });
    const body = await response.text();

    return {
      status: response.status,
      headers: response.headers,
      body,
    };
  } finally {
    await new Promise((resolve, reject) => {
      server.close((error) => (error ? reject(error) : resolve()));
    });
  }
}
