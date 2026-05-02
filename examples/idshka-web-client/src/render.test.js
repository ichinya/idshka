import assert from 'node:assert/strict';
import test from 'node:test';

import { renderHome } from './render.js';

test('renderHome shows only the idshka login button before authentication', () => {
  const html = renderHome({ user: null });

  assert.match(html, /Войти через idshka/);
  assert.match(html, /action="\/auth\/idshka\/redirect"/);
  assert.doesNotMatch(html, /Выйти/);
  assert.doesNotMatch(html, /userinfo/);
});

test('renderHome shows escaped raw userinfo and logout button after authentication', () => {
  const html = renderHome({
    user: {
      authenticatedAt: '2026-05-02T06:00:00.000Z',
      userinfo: {
        sub: '42',
        email: 'web-login@example.com',
        name: '<script>alert(1)</script>',
      },
      token: {
        token_type: 'Bearer',
        expires_in: 600,
        scope: 'openid profile email',
      },
    },
  });

  assert.match(html, /Выйти/);
  assert.doesNotMatch(html, /Войти через idshka/);
  assert.match(html, /"sub": "42"/);
  assert.match(html, /"email": "web-login@example.com"/);
  assert.match(html, /&lt;script&gt;alert\(1\)&lt;\/script&gt;/);
  assert.doesNotMatch(html, /<script>alert/);
});
