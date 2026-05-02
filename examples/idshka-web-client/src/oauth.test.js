import assert from 'node:assert/strict';
import test from 'node:test';

import {
  buildAuthorizationRedirect,
  exchangeCodeForUserInfo,
  pkceChallengeForVerifier,
  verifyReturnedState,
} from './oauth.js';

const config = {
  publicIssuerUrl: 'https://idshka.test',
  internalIssuerUrl: 'http://nginx',
  clientId: 'client_01test',
  clientSecret: 'secret_test',
  redirectUri: 'https://localhost:8443/auth/idshka/callback',
  scopes: 'openid profile email',
};

test('buildAuthorizationRedirect stores OAuth state and builds authorize URL', () => {
  const session = {};
  const redirect = buildAuthorizationRedirect(config, session);

  assert.ok(session.idshkaOAuth);
  assert.match(session.idshkaOAuth.state, /^[A-Za-z0-9_-]{32,}$/);
  assert.match(session.idshkaOAuth.nonce, /^[A-Za-z0-9_-]{32,}$/);
  assert.match(session.idshkaOAuth.codeVerifier, /^[A-Za-z0-9_-]{43,128}$/);

  const url = new URL(redirect);

  assert.equal(`${url.origin}${url.pathname}`, 'https://idshka.test/oauth/authorize');
  assert.equal(url.searchParams.get('response_type'), 'code');
  assert.equal(url.searchParams.get('client_id'), config.clientId);
  assert.equal(url.searchParams.get('redirect_uri'), config.redirectUri);
  assert.equal(url.searchParams.get('scope'), config.scopes);
  assert.equal(url.searchParams.get('state'), session.idshkaOAuth.state);
  assert.equal(url.searchParams.get('nonce'), session.idshkaOAuth.nonce);
  assert.equal(url.searchParams.get('code_challenge_method'), 'S256');
  assert.equal(
    url.searchParams.get('code_challenge'),
    pkceChallengeForVerifier(session.idshkaOAuth.codeVerifier),
  );
});

test('verifyReturnedState rejects mismatched callback state', () => {
  assert.doesNotThrow(() => verifyReturnedState('expected-state', 'expected-state'));
  assert.throws(
    () => verifyReturnedState('expected-state', 'other-state'),
    /OAuth state mismatch/,
  );
});

test('exchangeCodeForUserInfo exchanges the code, calls userinfo, and hides raw tokens', async () => {
  const calls = [];
  const fetchImpl = async (url, options = {}) => {
    calls.push({ url, options });

    if (url === 'http://nginx/oauth/token') {
      assert.equal(options.method, 'POST');
      assert.equal(options.headers['content-type'], 'application/x-www-form-urlencoded');

      const body = new URLSearchParams(options.body);
      assert.equal(body.get('grant_type'), 'authorization_code');
      assert.equal(body.get('client_id'), config.clientId);
      assert.equal(body.get('client_secret'), config.clientSecret);
      assert.equal(body.get('code'), 'code-123');
      assert.equal(body.get('redirect_uri'), config.redirectUri);
      assert.equal(body.get('code_verifier'), 'verifier-123');

      return jsonResponse(200, {
        access_token: 'raw-access-token',
        id_token: 'raw-id-token',
        token_type: 'Bearer',
        expires_in: 600,
        scope: 'openid profile email',
      });
    }

    if (url === 'http://nginx/oauth/userinfo') {
      assert.equal(options.headers.authorization, 'Bearer raw-access-token');

      return jsonResponse(200, {
        sub: '42',
        email: 'web-login@example.com',
        name: 'Web Login User',
      });
    }

    throw new Error(`Unexpected fetch URL: ${url}`);
  };

  const result = await exchangeCodeForUserInfo(config, {
    code: 'code-123',
    codeVerifier: 'verifier-123',
    fetchImpl,
  });

  assert.equal(calls.length, 2);
  assert.deepEqual(result, {
    userinfo: {
      sub: '42',
      email: 'web-login@example.com',
      name: 'Web Login User',
    },
    token: {
      token_type: 'Bearer',
      expires_in: 600,
      scope: 'openid profile email',
    },
  });
  assert.equal(JSON.stringify(result).includes('raw-access-token'), false);
  assert.equal(JSON.stringify(result).includes('raw-id-token'), false);
});

function jsonResponse(status, body) {
  return {
    ok: status >= 200 && status < 300,
    status,
    async json() {
      return body;
    },
    async text() {
      return JSON.stringify(body);
    },
  };
}
