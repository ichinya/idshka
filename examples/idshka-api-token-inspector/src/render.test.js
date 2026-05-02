import assert from 'node:assert/strict';
import test from 'node:test';

import { renderHome } from './render.js';

test('renderHome shows token form without echoing submitted raw tokens', () => {
  const rawToken = 'eyJ.raw.secret';
  const html = renderHome({
    result: {
      ok: true,
      header: { alg: 'RS256', kid: 'kid-1' },
      payload: {
        iss: 'http://localhost:8080',
        aud: 'chat.ie0.ru',
        sub: '1',
        site_id: 'site_01test',
        token_type: 'user_api',
        scope: 'orders.read',
        permissions: ['orders.read'],
        jti: 'jti-1',
      },
      summary: {
        token_type: 'user_api',
        subject: '1',
        site_id: 'site_01test',
        issuer: 'http://localhost:8080',
        audience: ['chat.ie0.ru'],
        kid: 'kid-1',
        alg: 'RS256',
        jti: 'jti-1',
        scopes: ['orders.read'],
        permissions: ['orders.read'],
        issued_at: null,
        not_before_at: null,
        expires_at: null,
        expires_in_seconds: null,
        expired: false,
        does_not_expire: true,
      },
      verification: { status: 'verified' },
      rawToken,
    },
  });

  assert.match(html, /API token inspector/);
  assert.match(html, /verified/);
  assert.match(html, /Never/);
  assert.match(html, /orders\.read/);
  assert.doesNotMatch(html, new RegExp(rawToken.replaceAll('.', '\\.')));
});
