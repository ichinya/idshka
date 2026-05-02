export function loadConfig(env = process.env) {
  const port = Number.parseInt(env.PORT || '8443', 10);
  const publicIssuerUrl = normalizeBaseUrl(env.IDSHKA_PUBLIC_URL || 'http://localhost:8080');
  const internalIssuerUrl = normalizeBaseUrl(env.IDSHKA_INTERNAL_URL || publicIssuerUrl);
  const redirectUri = env.IDSHKA_REDIRECT_URI || `https://localhost:${port}/auth/idshka/callback`;
  const clientId = env.IDSHKA_CLIENT_ID || '';
  const clientSecret = env.IDSHKA_CLIENT_SECRET || '';
  const sessionSecret =
    env.IDSHKA_SESSION_SECRET ||
    env.SESSION_SECRET ||
    'local-demo-session-secret-change-me';

  return {
    port,
    publicIssuerUrl,
    internalIssuerUrl,
    clientId,
    clientSecret,
    redirectUri,
    scopes: env.IDSHKA_SCOPES || 'openid profile email',
    sessionSecret,
    secureCookies: parseBoolean(env.SECURE_COOKIES, true),
    httpsCertPath: env.HTTPS_CERT_PATH || '/certs/localhost.crt',
    httpsKeyPath: env.HTTPS_KEY_PATH || '/certs/localhost.key',
    missing: missingRequired({ clientId, clientSecret }),
  };
}

function missingRequired(values) {
  return Object.entries(values)
    .filter(([, value]) => value === '')
    .map(([key]) => key);
}

function normalizeBaseUrl(value) {
  return value.endsWith('/') ? value.slice(0, -1) : value;
}

function parseBoolean(value, fallback) {
  if (value === undefined || value === '') {
    return fallback;
  }

  return ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
}
