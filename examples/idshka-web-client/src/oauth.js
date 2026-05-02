import { createHash, randomBytes, timingSafeEqual } from 'node:crypto';

export function pkceChallengeForVerifier(verifier) {
  return createHash('sha256').update(verifier).digest('base64url');
}

export function buildAuthorizationRedirect(config, session) {
  const state = randomToken(32);
  const nonce = randomToken(32);
  const codeVerifier = randomToken(64);

  session.idshkaOAuth = {
    state,
    nonce,
    codeVerifier,
    createdAt: new Date().toISOString(),
  };

  const url = endpointUrl(config.publicIssuerUrl, '/oauth/authorize');
  url.searchParams.set('response_type', 'code');
  url.searchParams.set('client_id', config.clientId);
  url.searchParams.set('redirect_uri', config.redirectUri);
  url.searchParams.set('scope', config.scopes);
  url.searchParams.set('state', state);
  url.searchParams.set('nonce', nonce);
  url.searchParams.set('code_challenge', pkceChallengeForVerifier(codeVerifier));
  url.searchParams.set('code_challenge_method', 'S256');

  return url.toString();
}

export function verifyReturnedState(expectedState, actualState) {
  if (!expectedState || !actualState) {
    throw new Error('OAuth state mismatch.');
  }

  const expected = Buffer.from(expectedState);
  const actual = Buffer.from(actualState);

  if (expected.length !== actual.length || !timingSafeEqual(expected, actual)) {
    throw new Error('OAuth state mismatch.');
  }
}

export async function exchangeCodeForUserInfo(
  config,
  { code, codeVerifier, fetchImpl = globalThis.fetch },
) {
  if (!code) {
    throw new Error('Authorization code is required.');
  }

  if (!codeVerifier) {
    throw new Error('PKCE verifier is required.');
  }

  const tokenPayload = await postTokenRequest(config, code, codeVerifier, fetchImpl);
  const accessToken = tokenPayload.access_token;

  if (!accessToken) {
    throw new Error('Token response does not include access_token.');
  }

  const userinfo = await fetchUserInfo(config, accessToken, fetchImpl);

  return {
    userinfo,
    token: {
      token_type: tokenPayload.token_type,
      expires_in: tokenPayload.expires_in,
      scope: tokenPayload.scope,
    },
  };
}

async function postTokenRequest(config, code, codeVerifier, fetchImpl) {
  const body = new URLSearchParams({
    grant_type: 'authorization_code',
    client_id: config.clientId,
    client_secret: config.clientSecret,
    code,
    redirect_uri: config.redirectUri,
    code_verifier: codeVerifier,
  });

  const response = await fetchImpl(endpointUrl(config.internalIssuerUrl, '/oauth/token').toString(), {
    method: 'POST',
    headers: {
      accept: 'application/json',
      'content-type': 'application/x-www-form-urlencoded',
    },
    body: body.toString(),
  });

  return parseJsonResponse(response, 'Token exchange failed.');
}

async function fetchUserInfo(config, accessToken, fetchImpl) {
  const response = await fetchImpl(endpointUrl(config.internalIssuerUrl, '/oauth/userinfo').toString(), {
    method: 'GET',
    headers: {
      accept: 'application/json',
      authorization: `Bearer ${accessToken}`,
    },
  });

  return parseJsonResponse(response, 'Userinfo request failed.');
}

async function parseJsonResponse(response, message) {
  const payload = await response.json().catch(async () => {
    const text = await response.text().catch(() => '');

    return { error: 'invalid_json', message: text };
  });

  if (!response.ok) {
    const errorCode = payload?.error ? ` ${payload.error}` : '';
    const error = new Error(`${message}${errorCode}`.trim());
    error.status = response.status;
    error.payload = payload;
    throw error;
  }

  return payload;
}

function endpointUrl(baseUrl, path) {
  return new URL(path, withTrailingSlash(baseUrl));
}

function withTrailingSlash(value) {
  return value.endsWith('/') ? value : `${value}/`;
}

function randomToken(bytes) {
  return randomBytes(bytes).toString('base64url');
}
