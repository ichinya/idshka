export function loadConfig(env = process.env) {
  const port = Number.parseInt(env.PORT || '8444', 10);

  return {
    port,
    issuer: normalizeBaseUrl(env.IDSHKA_ISSUER || 'http://localhost:8080'),
    jwksUrl: env.IDSHKA_JWKS_URL || 'http://nginx/oauth/jwks.json',
    jwksTimeoutMs: Number.parseInt(env.IDSHKA_JWKS_TIMEOUT_MS || '15000', 10),
    jwksRetryCount: Number.parseInt(env.IDSHKA_JWKS_RETRY_COUNT || '1', 10),
    clockToleranceSeconds: Number.parseInt(env.IDSHKA_CLOCK_TOLERANCE_SECONDS || '5', 10),
    httpsCertPath: env.HTTPS_CERT_PATH || '/certs/localhost.crt',
    httpsKeyPath: env.HTTPS_KEY_PATH || '/certs/localhost.key',
  };
}

function normalizeBaseUrl(value) {
  return value.endsWith('/') ? value.slice(0, -1) : value;
}
