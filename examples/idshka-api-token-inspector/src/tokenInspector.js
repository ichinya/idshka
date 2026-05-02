import { createRemoteJWKSet, decodeJwt, decodeProtectedHeader, jwtVerify } from 'jose';

const jwksCache = new Map();
const JWKS_TIMEOUT_ERROR = 'ERR_JWKS_TIMEOUT';

export async function inspectToken(rawInput, config, { now = new Date() } = {}) {
  const token = normalizeBearerToken(rawInput);

  if (!token) {
    return {
      ok: false,
      error: 'missing_token',
      message: 'Paste a Bearer token or raw JWT.',
    };
  }

  let header;
  let payload;

  try {
    header = decodeProtectedHeader(token);
    payload = decodeJwt(token);
  } catch (error) {
    return {
      ok: false,
      error: 'malformed_token',
      message: error.message,
    };
  }

  const result = {
    ok: true,
    header,
    payload,
    summary: summarizeToken(header, payload, now),
    verification: {
      status: 'not_checked',
      issuer: config.issuer,
      jwks_url: config.jwksUrl,
    },
  };

  try {
    const { verified, attempts } = await verifyWithRetry(token, config);

    result.header = verified.protectedHeader;
    result.payload = verified.payload;
    result.summary = summarizeToken(verified.protectedHeader, verified.payload, now);
    result.verification = {
      status: 'verified',
      issuer: config.issuer,
      jwks_url: config.jwksUrl,
      attempts,
    };
  } catch (error) {
    result.verification = {
      status: 'failed',
      issuer: config.issuer,
      jwks_url: config.jwksUrl,
      error: error.code || 'verification_failed',
      message: error.message,
      attempts: error.attempts || 1,
    };
  }

  return result;
}

async function verifyWithRetry(token, config) {
  const retryCount = Math.max(0, Number.parseInt(config.jwksRetryCount ?? '0', 10));
  let attempts = 0;

  while (attempts <= retryCount) {
    attempts += 1;

    try {
      const verified = await jwtVerify(token, jwksFor(config), {
        issuer: config.issuer,
        clockTolerance: config.clockToleranceSeconds,
      });

      return { verified, attempts };
    } catch (error) {
      error.attempts = attempts;

      if (error.code !== JWKS_TIMEOUT_ERROR || attempts > retryCount) {
        throw error;
      }

      jwksCache.delete(jwksCacheKey(config));
    }
  }

  throw new Error('Verification failed.');
}

export function summarizeToken(header, payload, now = new Date()) {
  const nowSeconds = Math.floor(now.getTime() / 1000);
  const expiresAt = timestampToIso(payload.exp);
  const notBeforeAt = timestampToIso(payload.nbf);
  const issuedAt = timestampToIso(payload.iat);

  return {
    token_type: stringClaim(payload.token_type),
    subject: stringClaim(payload.sub),
    site_id: stringClaim(payload.site_id),
    issuer: stringClaim(payload.iss),
    audience: normalizeAudience(payload.aud),
    kid: stringClaim(header.kid),
    alg: stringClaim(header.alg),
    jti: stringClaim(payload.jti),
    scopes: splitScope(payload.scope),
    permissions: normalizeStringList(payload.permissions),
    issued_at: issuedAt,
    not_before_at: notBeforeAt,
    expires_at: expiresAt,
    expires_in_seconds: typeof payload.exp === 'number' ? payload.exp - nowSeconds : null,
    expired: typeof payload.exp === 'number' ? payload.exp <= nowSeconds : false,
    does_not_expire: typeof payload.exp !== 'number',
  };
}

function normalizeBearerToken(rawInput) {
  const value = String(rawInput || '').trim();

  if (value === '') {
    return '';
  }

  const bearer = value.match(/^Bearer\s+(.+)$/i);

  return bearer ? bearer[1].trim() : value;
}

function jwksFor(config) {
  const key = jwksCacheKey(config);

  if (!jwksCache.has(key)) {
    jwksCache.set(key, createRemoteJWKSet(new URL(config.jwksUrl), {
      timeoutDuration: Math.max(50, Number.parseInt(config.jwksTimeoutMs ?? '15000', 10)),
    }));
  }

  return jwksCache.get(key);
}

function jwksCacheKey(config) {
  return `${config.jwksUrl}|${config.jwksTimeoutMs ?? 15000}`;
}

function timestampToIso(value) {
  if (typeof value !== 'number') {
    return null;
  }

  return new Date(value * 1000).toISOString();
}

function stringClaim(value) {
  return typeof value === 'string' ? value : null;
}

function splitScope(value) {
  if (typeof value !== 'string') {
    return [];
  }

  return value.split(/\s+/).filter(Boolean);
}

function normalizeAudience(value) {
  if (typeof value === 'string') {
    return [value];
  }

  return normalizeStringList(value);
}

function normalizeStringList(value) {
  if (!Array.isArray(value)) {
    return [];
  }

  return value.filter((item) => typeof item === 'string');
}
