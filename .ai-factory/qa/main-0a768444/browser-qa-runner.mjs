import { execFileSync, spawn } from 'node:child_process';
import { createHash, randomBytes } from 'node:crypto';
import { mkdir, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const rootDir = process.cwd();
const qaDir = path.join(rootDir, '.ai-factory', 'qa', 'main-0a768444');
const dbPath = path.join(rootDir, 'storage', 'framework', 'testing', 'browser-qa.sqlite');
const baseUrl = 'http://127.0.0.1:8090';
const chromePort = 9300 + Math.floor(Math.random() * 500);
const chromeUserDataDir = path.join(rootDir, 'storage', 'framework', 'testing', 'chrome-browser-qa');
const chromePath = process.env.CHROME_PATH
  ?? path.join(process.env.LOCALAPPDATA ?? '', 'Google', 'Chrome', 'Application', 'chrome.exe');

const env = {
  ...process.env,
  APP_ENV: 'local',
  APP_URL: baseUrl,
  DB_CONNECTION: 'sqlite',
  DB_DATABASE: dbPath,
  SESSION_DRIVER: 'file',
  CACHE_STORE: 'array',
  QUEUE_CONNECTION: 'sync',
  MAIL_MAILER: 'log',
  LOG_CHANNEL: 'single',
};

const results = [];
const artifacts = [];
let page = null;
let chrome = null;

function record(id, name, status, evidence, details = {}) {
  results.push({
    id,
    name,
    status,
    evidence,
    ...details,
  });
}

function pass(id, name, evidence, details) {
  record(id, name, 'PASS', evidence, details);
}

function fail(id, name, evidence, details) {
  record(id, name, 'FAIL', evidence, details);
}

function partial(id, name, evidence, details) {
  record(id, name, 'PARTIAL', evidence, details);
}

function runPhp(args) {
  return execFileSync('php', args, {
    cwd: rootDir,
    env,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

function tinker(code) {
  return runPhp(['artisan', 'tinker', '--execute', code]).trim();
}

function tinkerJson(code) {
  const output = tinker(`echo json_encode(${code});`);
  const match = output.match(/(\{[\s\S]*\}|\[[\s\S]*\])\s*$/);
  if (!match) {
    throw new Error(`No JSON found in tinker output: ${output}`);
  }

  return JSON.parse(match[1]);
}

async function waitForUrl(url, timeoutMs = 15000) {
  const startedAt = Date.now();
  let lastError = null;

  while (Date.now() - startedAt < timeoutMs) {
    try {
      const response = await fetch(url);

      if (response.ok) {
        return response;
      }

      lastError = new Error(`HTTP ${response.status}`);
    } catch (error) {
      lastError = error;
    }

    await new Promise((resolve) => setTimeout(resolve, 250));
  }

  throw lastError ?? new Error(`Timed out waiting for ${url}`);
}

async function connectCdp(webSocketUrl) {
  const ws = new WebSocket(webSocketUrl);
  const pending = new Map();
  const listeners = new Map();
  let nextId = 1;

  await new Promise((resolve, reject) => {
    ws.addEventListener('open', resolve, { once: true });
    ws.addEventListener('error', reject, { once: true });
  });

  ws.addEventListener('message', (event) => {
    const message = JSON.parse(event.data);

    if (message.id !== undefined) {
      const callback = pending.get(message.id);

      if (callback) {
        pending.delete(message.id);

        if (message.error) {
          callback.reject(new Error(message.error.message));
        } else {
          callback.resolve(message.result ?? {});
        }
      }

      return;
    }

    for (const listener of listeners.get(message.method) ?? []) {
      listener(message.params ?? {});
    }
  });

  return {
    send(method, params = {}) {
      const id = nextId++;

      return new Promise((resolve, reject) => {
        pending.set(id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params }));
      });
    },
    once(method) {
      return new Promise((resolve) => {
        const callback = (params) => {
          const methodListeners = listeners.get(method) ?? [];
          listeners.set(method, methodListeners.filter((item) => item !== callback));
          resolve(params);
        };

        listeners.set(method, [...(listeners.get(method) ?? []), callback]);
      });
    },
    close() {
      ws.close();
    },
  };
}

async function waitForPageLoad(timeoutMs = 10000) {
  await Promise.race([
    page.once('Page.loadEventFired'),
    new Promise((resolve) => setTimeout(resolve, timeoutMs)),
  ]);
}

async function navigate(url) {
  const load = page.once('Page.loadEventFired');
  await page.send('Page.navigate', { url });
  await Promise.race([
    load,
    new Promise((resolve) => setTimeout(resolve, 10000)),
  ]);
  await new Promise((resolve) => setTimeout(resolve, 250));
}

async function evaluate(expression) {
  const response = await page.send('Runtime.evaluate', {
    expression,
    awaitPromise: true,
    returnByValue: true,
  });

  if (response.exceptionDetails) {
    throw new Error(response.exceptionDetails.text ?? 'Runtime.evaluate failed');
  }

  return response.result?.value;
}

function pageRequestExpression(pathname, options = {}) {
  return `(
    async () => {
      const tokenCookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));
      const xsrf = tokenCookie ? decodeURIComponent(tokenCookie.split('=').slice(1).join('=')) : '';
      const headers = Object.assign({
        'Accept': ${JSON.stringify(options.accept ?? 'text/html,application/json')},
      }, ${JSON.stringify(options.headers ?? {})});
      if (xsrf) {
        headers['X-XSRF-TOKEN'] = xsrf;
      }
      const response = await fetch(${JSON.stringify(pathname)}, {
        method: ${JSON.stringify(options.method ?? 'GET')},
        credentials: 'same-origin',
        redirect: ${JSON.stringify(options.redirect ?? 'follow')},
        headers,
        body: ${options.body === undefined ? 'undefined' : JSON.stringify(options.body)},
      });
      return {
        status: response.status,
        type: response.type,
        redirected: response.redirected,
        url: response.url,
        text: await response.text(),
      };
    }
  )()`;
}

async function appGet(pathname, options = {}) {
  await ensureAppOrigin();
  return evaluate(pageRequestExpression(pathname, { ...options, method: 'GET' }));
}

async function formPost(pathname, data, options = {}) {
  await ensureAppOrigin();
  const body = new URLSearchParams(data).toString();

  return evaluate(pageRequestExpression(pathname, {
    method: 'POST',
    body,
    accept: options.accept,
    redirect: options.redirect,
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
      ...(options.headers ?? {}),
    },
  }));
}

async function ensureAppOrigin() {
  let href = '';

  try {
    href = await evaluate('location.href');
  } catch {
    href = '';
  }

  if (!href.startsWith(baseUrl)) {
    await navigate(`${baseUrl}/`);
  }
}

async function saveScreenshot(name, width, height) {
  await page.send('Emulation.setDeviceMetricsOverride', {
    width,
    height,
    deviceScaleFactor: 1,
    mobile: width < 700,
  });
  await navigate(`${baseUrl}/portal`);
  const screenshot = await page.send('Page.captureScreenshot', {
    format: 'png',
    fromSurface: true,
    captureBeyondViewport: true,
  });
  const target = path.join(qaDir, name);
  await writeFile(target, Buffer.from(screenshot.data, 'base64'));
  artifacts.push(path.relative(rootDir, target).replaceAll('\\', '/'));
}

async function getResponsiveLayoutSnapshot() {
  return evaluate(`(() => {
    const viewportWidth = document.documentElement.clientWidth;
    const documentScrollWidth = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth);
    const clippedControls = Array.from(document.querySelectorAll('button,input,select,textarea,a'))
      .map((element) => {
        const rect = element.getBoundingClientRect();

        return {
          tag: element.tagName,
          text: (element.innerText || element.value || element.name || '').trim().slice(0, 80),
          left: Math.round(rect.left),
          right: Math.round(rect.right),
          width: Math.round(rect.width),
        };
      })
      .filter((item) => item.width > 0 && (item.left < -1 || item.right > viewportWidth + 1));
    const auditHeading = Array.from(document.querySelectorAll('h2'))
      .find((element) => element.textContent.trim() === 'Audit');
    const auditScrollContainer = auditHeading?.parentElement?.nextElementSibling ?? null;
    const auditStyle = auditScrollContainer ? getComputedStyle(auditScrollContainer) : null;
    const audit = auditScrollContainer ? {
      overflowX: auditStyle.overflowX,
      clientWidth: auditScrollContainer.clientWidth,
      scrollWidth: auditScrollContainer.scrollWidth,
    } : null;

    return {
      viewportWidth,
      documentScrollWidth,
      clippedControls,
      audit,
    };
  })()`);
}

function includesAll(text, snippets) {
  return snippets.every((snippet) => text.includes(snippet));
}

function extractFirst(text, pattern, label) {
  const match = text.match(pattern);

  if (!match) {
    throw new Error(`Could not extract ${label}`);
  }

  return match[1] ?? match[0];
}

async function resetDatabase() {
  await mkdir(path.dirname(dbPath), { recursive: true });
  await rm(dbPath, { force: true });
  await writeFile(dbPath, '');
  runPhp(['artisan', 'migrate:fresh', '--force']);
  tinker('app(App\\Domain\\Issuer\\Services\\SigningKeyService::class)->createActiveKey();');
}

async function startChrome() {
  await rm(chromeUserDataDir, { recursive: true, force: true });
  chrome = spawn(chromePath, [
    `--remote-debugging-port=${chromePort}`,
    '--remote-allow-origins=*',
    `--user-data-dir=${chromeUserDataDir}`,
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    'about:blank',
  ], {
    stdio: 'ignore',
    detached: false,
  });

  await waitForUrl(`http://127.0.0.1:${chromePort}/json/version`);
  const target = await fetch(`http://127.0.0.1:${chromePort}/json/new?about:blank`, { method: 'PUT' }).then((res) => res.json());
  page = await connectCdp(target.webSocketDebuggerUrl);
  await page.send('Page.enable');
  await page.send('Page.setBypassCSP', { enabled: true });
  await page.send('Runtime.enable');
  await page.send('Network.enable');
  await navigate(`${baseUrl}/`);
  const href = await evaluate('location.href');

  if (!href.startsWith(baseUrl)) {
    throw new Error(`Chrome did not navigate to app origin; current URL is ${href}`);
  }
}

async function registerOwner(name, email) {
  await navigate(`${baseUrl}/`);
  const response = await formPost('/register', {
    name,
    email,
    password: 'password123',
    password_confirmation: 'password123',
  }, {
    accept: 'application/json',
  });

  if (response.status !== 201) {
    throw new Error(`Register failed for ${email}: ${response.status} ${response.text.slice(0, 300)}`);
  }

  return JSON.parse(response.text);
}

async function logout() {
  await formPost('/logout', {}, { accept: 'application/json' });
  await navigate(`${baseUrl}/`);
}

function markVerified(domain, mode = null) {
  tinker(`
    $site = App\\Domain\\Sites\\Models\\Site::where('normalized_domain', '${domain}')->firstOrFail();
    $site->forceFill(['verification_status' => 'verified', 'verified_at' => now()])->save();
    ${mode === null ? '' : `App\\Domain\\Sites\\Models\\SiteMode::firstOrCreate(['site_id' => $site->id, 'mode' => '${mode}'], ['enabled_at' => now()]);`}
  `);
}

function getSiteIds() {
  return tinkerJson("App\\Domain\\Sites\\Models\\Site::query()->pluck('id', 'normalized_domain')->all()");
}

function getSiteModes(siteId) {
  return tinkerJson(`App\\Domain\\Sites\\Models\\SiteMode::query()->where('site_id', '${siteId}')->pluck('mode')->all()`);
}

function getLatestTokenId() {
  return Number(tinker("echo App\\Domain\\Issuer\\Models\\ApiToken::query()->latest('id')->value('id');").match(/\d+/)?.[0]);
}

function getLatestClientId() {
  return Number(tinker("echo App\\Domain\\OidcClients\\Models\\OidcClient::query()->latest('id')->value('id');").match(/\d+/)?.[0]);
}

function getActiveClientId() {
  return Number(tinker("echo App\\Domain\\OidcClients\\Models\\OidcClient::query()->whereNull('revoked_at')->latest('id')->value('id');").match(/\d+/)?.[0]);
}

function getClientPublicId(clientRowId) {
  return tinker(`echo App\\Domain\\OidcClients\\Models\\OidcClient::query()->findOrFail(${clientRowId})->client_id;`).split(/\r?\n/).pop().trim();
}

function getStateSnapshot() {
  return tinkerJson(`[
    'sites' => App\\Domain\\Sites\\Models\\Site::query()->count(),
    'api_tokens' => App\\Domain\\Issuer\\Models\\ApiToken::query()->count(),
    'revoked_tokens' => App\\Domain\\Issuer\\Models\\ApiToken::query()->whereNotNull('revoked_at')->count(),
    'clients' => App\\Domain\\OidcClients\\Models\\OidcClient::query()->count(),
    'revoked_clients' => App\\Domain\\OidcClients\\Models\\OidcClient::query()->whereNotNull('revoked_at')->count(),
    'redirect_uris' => App\\Domain\\OidcClients\\Models\\OidcRedirectUri::query()->count(),
    'audit_events' => App\\Domain\\Audit\\Models\\AuditEvent::query()->count(),
  ]`);
}

async function nodeAuthorizeRequest(clientId, redirectUri, verifier, challenge) {
  const cookies = await page.send('Network.getAllCookies');
  const cookieHeader = cookies.cookies
    .filter((cookie) => cookie.domain === '127.0.0.1')
    .map((cookie) => `${cookie.name}=${cookie.value}`)
    .join('; ');
  const url = new URL(`${baseUrl}/oauth/authorize`);
  url.searchParams.set('response_type', 'code');
  url.searchParams.set('client_id', clientId);
  url.searchParams.set('redirect_uri', redirectUri);
  url.searchParams.set('scope', 'openid profile email');
  url.searchParams.set('state', 'qa-state-017');
  url.searchParams.set('nonce', 'qa-nonce-017');
  url.searchParams.set('code_challenge', challenge);
  url.searchParams.set('code_challenge_method', 'S256');

  const response = await fetch(url, {
    redirect: 'manual',
    headers: {
      Cookie: cookieHeader,
      Accept: 'text/html,application/json',
    },
  });

  const location = response.headers.get('location') ?? '';
  const redirected = new URL(location);
  const code = redirected.searchParams.get('code');

  if (response.status !== 302 || !code) {
    throw new Error(`Authorize failed: status=${response.status}, location=${location}, body=${await response.text()}`);
  }

  const tokenResponse = await fetch(`${baseUrl}/oauth/token`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      grant_type: 'authorization_code',
      client_id: clientId,
      client_secret: activeClientSecret,
      code,
      redirect_uri: redirectUri,
      code_verifier: verifier,
    }),
  });
  const tokenPayload = await tokenResponse.json();

  if (tokenResponse.status !== 200 || !tokenPayload.access_token) {
    throw new Error(`Token exchange failed: status=${tokenResponse.status}, payload=${JSON.stringify(tokenPayload)}`);
  }

  const userInfoResponse = await fetch(`${baseUrl}/oauth/userinfo`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${tokenPayload.access_token}`,
    },
  });
  const userInfo = await userInfoResponse.json();

  if (userInfoResponse.status !== 200 || userInfo.email !== 'owner-a@example.test') {
    throw new Error(`Userinfo failed: status=${userInfoResponse.status}, payload=${JSON.stringify(userInfo)}`);
  }

  return { code, tokenPayload, userInfo };
}

let activeClientSecret = '';

try {
  await resetDatabase();
  await startChrome();

  const guestPortal = await appGet('/portal', { redirect: 'manual' });
  if (guestPortal.status === 401 || guestPortal.status === 302 || guestPortal.type === 'opaqueredirect' || guestPortal.url.includes('/login')) {
    pass('TC-002', 'Гость не может открыть портал', `Guest /portal returned ${guestPortal.type}/${guestPortal.status} without showing the dashboard.`);
  } else {
    fail('TC-002', 'Гость не может открыть портал', `Guest /portal returned ${guestPortal.status}; expected auth redirect/401.`, {
      observed_error: guestPortal.text.includes('Route [login] not defined') ? 'Route [login] not defined' : guestPortal.text.slice(0, 200),
    });
  }

  await page.send('Network.clearBrowserCookies');
  await registerOwner('Owner A', 'owner-a@example.test');

  let portal = await appGet('/portal');
  if (portal.status === 200 && includesAll(portal.text, ['Мои сайты', 'API tokens', 'Web clients', 'Audit'])) {
    pass('TC-001', 'Владелец открывает портал', 'Authenticated owner sees portal sections.');
  } else {
    fail('TC-001', 'Владелец открывает портал', `Authenticated /portal status=${portal.status}.`);
  }

  portal = await formPost('/portal/sites', {
    domain: 'https://Example App.ru/path?query=1',
    display_name: 'Example App',
  });
  const mainSiteId = getSiteIds()['example.test'];
  if (portal.status === 200 && includesAll(portal.text, ['example.test', '_idshka.example.test', 'idshka-site-verification='])) {
    pass('TC-003', 'Владелец создает сайт и видит инструкции верификации', `Created ${mainSiteId} and saw DNS/file instructions.`);
  } else {
    fail('TC-003', 'Владелец создает сайт и видит инструкции верификации', `Site creation response status=${portal.status}.`);
  }

  const modesBeforeUnverified = getSiteModes(mainSiteId);
  await formPost(`/portal/sites/${mainSiteId}/modes/api_resource`, {});
  await formPost(`/portal/sites/${mainSiteId}/modes/web_client`, {});
  const modesAfterUnverified = getSiteModes(mainSiteId);
  if (modesBeforeUnverified.length === 0 && modesAfterUnverified.length === 0) {
    pass('TC-004', 'Неверифицированный сайт не получает production modes', 'Both mode enable attempts left the pending site without production modes.');
  } else {
    fail('TC-004', 'Неверифицированный сайт не получает production modes', 'At least one unverified mode enable attempt changed site modes.', {
      modesBeforeUnverified,
      modesAfterUnverified,
    });
  }

  markVerified('example.test');
  const apiMode = await formPost(`/portal/sites/${mainSiteId}/modes/api_resource`, {});
  const webMode = await formPost(`/portal/sites/${mainSiteId}/modes/web_client`, {});
  if (apiMode.text.includes('API resource enabled') && webMode.text.includes('Web client enabled') && webMode.text.includes('api_resource') && webMode.text.includes('web_client')) {
    pass('TC-005', 'Верифицированный владелец включает оба режима сайта', 'Both api_resource and web_client modes appear on dashboard.');
  } else {
    fail('TC-005', 'Верифицированный владелец включает оба режима сайта', 'Verified mode enable flow did not show expected notices/modes.');
  }

  const tokenIssue = await formPost('/portal/api-tokens', {
    site_id: mainSiteId,
    scopes: 'orders.read',
    permissions: 'orders.read',
  });
  const rawToken = extractFirst(tokenIssue.text, /eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/, 'raw API token');
  const tokenId = getLatestTokenId();
  const tokenRefresh = await appGet('/portal');
  if (tokenIssue.status === 200 && rawToken && !tokenRefresh.text.includes(rawToken)) {
    pass('TC-006', 'Владелец выпускает API token и видит raw token один раз', `Raw token appeared once for token id ${tokenId} and disappeared after reload.`);
  } else {
    fail('TC-006', 'Владелец выпускает API token и видит raw token один раз', 'Raw token was not one-time visible.');
  }

  await formPost('/portal/sites', { domain: 'pending.example.test', display_name: 'Pending' });
  await formPost('/portal/sites', { domain: 'web-only.example.test', display_name: 'Web only' });
  await formPost('/portal/sites', { domain: 'api-only.example.test', display_name: 'API only' });
  markVerified('web-only.example.test', 'web_client');
  markVerified('api-only.example.test', 'api_resource');
  const siteIds = getSiteIds();
  const pendingToken = await formPost('/portal/api-tokens', {
    site_id: siteIds['pending.example.test'],
    scopes: 'orders.read',
    permissions: 'orders.read',
  });
  const webOnlyToken = await formPost('/portal/api-tokens', {
    site_id: siteIds['web-only.example.test'],
    scopes: 'orders.read',
    permissions: 'orders.read',
  });
  if (!/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/.test(pendingToken.text) && !/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/.test(webOnlyToken.text)) {
    pass('TC-007', 'Выпуск API token отклоняется для неeligible site', 'Pending and web-only sites did not receive raw API tokens.');
  } else {
    fail('TC-007', 'Выпуск API token отклоняется для неeligible site', 'An ineligible site received a raw API token.');
  }

  const clientCreate = await formPost('/portal/clients', {
    site_id: mainSiteId,
    name: 'Example Web',
    redirect_uri: 'https://example.test/auth/idshka/callback',
  });
  activeClientSecret = extractFirst(clientCreate.text, /secret_[A-Za-z0-9]+/, 'client secret');
  const clientRowId = getLatestClientId();
  const activeClientPublicId = getClientPublicId(clientRowId);
  const clientRefresh = await appGet('/portal');
  if (clientCreate.status === 200 && activeClientSecret && !clientRefresh.text.includes(activeClientSecret) && clientRefresh.text.includes(activeClientPublicId)) {
    pass('TC-009', 'Владелец создает OIDC web client и видит secret один раз', `Client ${activeClientPublicId} secret appeared once and disappeared after reload.`);
  } else {
    fail('TC-009', 'Владелец создает OIDC web client и видит secret один раз', 'Client secret one-time display check failed.');
  }

  const pendingClient = await formPost('/portal/clients', {
    site_id: siteIds['pending.example.test'],
    name: 'Pending Client',
    redirect_uri: 'https://example.test/auth/idshka/callback',
  });
  const apiOnlyClient = await formPost('/portal/clients', {
    site_id: siteIds['api-only.example.test'],
    name: 'API Only Client',
    redirect_uri: 'https://example.test/auth/idshka/callback',
  });
  if (!pendingClient.text.includes('Client secret') && !apiOnlyClient.text.includes('Client secret')) {
    pass('TC-010', 'Создание OIDC client отклоняется для неeligible site', 'Pending and api-only sites did not receive client secrets.');
  } else {
    fail('TC-010', 'Создание OIDC client отклоняется для неeligible site', 'An ineligible site received an OIDC client secret.');
  }

  const validRedirect = await formPost(`/portal/clients/${clientRowId}/redirect-uris`, {
    redirect_uri: 'https://example.test/auth/idshka/tenant/callback',
  });
  const httpRedirect = await formPost(`/portal/clients/${clientRowId}/redirect-uris`, {
    redirect_uri: 'http://example.test/auth/idshka/callback',
  });
  const wildcardRedirect = await formPost(`/portal/clients/${clientRowId}/redirect-uris`, {
    redirect_uri: 'https://*.example.test/auth/idshka/callback',
  });
  const malformedRedirect = await formPost(`/portal/clients/${clientRowId}/redirect-uris`, {
    redirect_uri: 'not-a-url',
  });
  if (validRedirect.text.includes('Redirect URI added') && !httpRedirect.text.includes('Redirect URI added') && !wildcardRedirect.text.includes('Redirect URI added') && !malformedRedirect.text.includes('Redirect URI added')) {
    pass('TC-011', 'Redirect URI принимает exact HTTPS и отклоняет unsafe values', 'Valid HTTPS redirect accepted; http, wildcard and malformed values rejected.');
  } else {
    fail('TC-011', 'Redirect URI принимает exact HTTPS и отклоняет unsafe values', 'Redirect URI validation result differed from expected.');
  }

  const beforeDuplicate = getStateSnapshot();
  await formPost(`/portal/clients/${clientRowId}/redirect-uris`, {
    redirect_uri: 'https://example.test/auth/idshka/callback',
  });
  const afterDuplicate = getStateSnapshot();
  if (afterDuplicate.redirect_uris === beforeDuplicate.redirect_uris && afterDuplicate.audit_events === beforeDuplicate.audit_events) {
    pass('TC-012', 'Duplicate redirect URI не создает duplicate visible entries', 'Duplicate redirect did not add a row or audit event.');
  } else {
    fail('TC-012', 'Duplicate redirect URI не создает duplicate visible entries', 'Duplicate redirect changed redirect/audit counts.', { beforeDuplicate, afterDuplicate });
  }

  const verifier = `${randomBytes(48).toString('base64url')}qa`.slice(0, 64);
  const challenge = createHash('sha256').update(verifier).digest('base64url');
  await nodeAuthorizeRequest(activeClientPublicId, 'https://example.test/auth/idshka/callback', verifier, challenge);
  pass('TC-017', 'Existing OIDC web login работает с portal-created client', 'Authorize, token exchange and userinfo succeeded for portal-created client.');

  const tokenBeforeInvalid = getStateSnapshot();
  await formPost(`/portal/api-tokens/${tokenId}/revoke`, {});
  await formPost(`/portal/api-tokens/${tokenId}/revoke`, { confirm: 'yes' });
  const tokenAfterInvalid = getStateSnapshot();
  const tokenBeforeValid = getStateSnapshot();
  const validTokenRevoke = await formPost(`/portal/api-tokens/${tokenId}/revoke`, { confirm: 'revoke' });
  const tokenAfterValid = getStateSnapshot();
  if (tokenAfterInvalid.revoked_tokens === tokenBeforeInvalid.revoked_tokens && validTokenRevoke.text.includes('Token revoked') && tokenAfterValid.revoked_tokens === tokenBeforeValid.revoked_tokens + 1) {
    pass('TC-008', 'Revoke API token требует явного подтверждения', 'Empty/yes confirmations did not mutate state; exact revoke revoked the token.');
  } else {
    fail('TC-008', 'Revoke API token требует явного подтверждения', 'API token revoke confirmation behavior differed from expected.', { tokenBeforeValid, tokenAfterValid });
  }

  const clientBeforeInvalid = getStateSnapshot();
  await formPost(`/portal/clients/${clientRowId}/revoke`, {});
  await formPost(`/portal/clients/${clientRowId}/revoke`, { confirm: 'delete' });
  const clientAfterInvalid = getStateSnapshot();
  const clientBeforeValid = getStateSnapshot();
  const validClientRevoke = await formPost(`/portal/clients/${clientRowId}/revoke`, { confirm: 'revoke' });
  const clientAfterValid = getStateSnapshot();
  if (clientAfterInvalid.revoked_clients === clientBeforeInvalid.revoked_clients && validClientRevoke.text.includes('Client revoked') && clientAfterValid.revoked_clients === clientBeforeValid.revoked_clients + 1) {
    pass('TC-013', 'Revoke OIDC client требует явного подтверждения', 'Empty/delete confirmations did not mutate state; exact revoke revoked the client.');
  } else {
    fail('TC-013', 'Revoke OIDC client требует явного подтверждения', 'OIDC client revoke confirmation behavior differed from expected.', { clientBeforeValid, clientAfterValid });
  }

  const beforeRevokedRedirect = getStateSnapshot();
  await formPost(`/portal/clients/${clientRowId}/redirect-uris`, {
    redirect_uri: 'https://example.test/auth/idshka/after-revoke',
  });
  const afterRevokedRedirect = getStateSnapshot();
  if (afterRevokedRedirect.redirect_uris === beforeRevokedRedirect.redirect_uris) {
    pass('TC-016', 'Revoked OIDC client не принимает новый redirect URI', 'Revoked client rejected a new redirect URI.');
  } else {
    fail('TC-016', 'Revoked OIDC client не принимает новый redirect URI', 'Revoked client redirect add behavior differed from expected.', { beforeRevokedRedirect, afterRevokedRedirect });
  }

  const auditPage = await appGet('/portal');
  const auditRows = tinkerJson("App\\Domain\\Audit\\Models\\AuditEvent::query()->orderBy('id')->get(['action', 'metadata'])->toArray()");
  const auditJson = JSON.stringify(auditRows);
  if (includesAll(auditPage.text, ['site.connected', 'site.mode_enabled', 'issuer.user_api_token_issued', 'oidc.client_created', 'oidc.redirect_uri_added']) && !auditJson.includes(rawToken) && !auditJson.includes(activeClientSecret)) {
    pass('TC-015', 'Audit table записывает lifecycle events без secrets', 'Portal showed lifecycle audit events and audit metadata did not contain raw token/client secret.');
  } else {
    fail('TC-015', 'Audit table записывает lifecycle events без secrets', 'Audit visibility or secret exclusion check failed.');
  }

  await logout();
  await registerOwner('Owner B', 'owner-b@example.test');
  const ownerBDashboard = await appGet('/portal');
  const foreignMode = await formPost(`/portal/sites/${mainSiteId}/modes/api_resource`, {});
  const foreignTokenRevoke = await formPost(`/portal/api-tokens/${tokenId}/revoke`, { confirm: 'revoke' });
  const foreignRedirectAdd = await formPost(`/portal/clients/${clientRowId}/redirect-uris`, {
    redirect_uri: 'https://evil.example.test/callback',
  });
  const foreignClientRevoke = await formPost(`/portal/clients/${clientRowId}/revoke`, { confirm: 'revoke' });
  if (!ownerBDashboard.text.includes(mainSiteId) && !ownerBDashboard.text.includes(activeClientPublicId) && foreignMode.status === 404 && foreignTokenRevoke.status === 404 && foreignRedirectAdd.status === 404 && foreignClientRevoke.status === 404) {
    pass('TC-014', 'Чужой владелец не может управлять ресурсами другого владельца', 'Owner B dashboard hid Owner A data and direct mutation attempts returned 404.');
  } else {
    fail('TC-014', 'Чужой владелец не может управлять ресурсами другого владельца', 'Foreign-owner isolation differed from expected.', {
      foreignStatuses: {
        mode: foreignMode.status,
        token: foreignTokenRevoke.status,
        redirect: foreignRedirectAdd.status,
        client: foreignClientRevoke.status,
      },
    });
  }

  await logout();
  await formPost('/login', {
    email: 'owner-a@example.test',
    password: 'password123',
  }, { accept: 'application/json' });
  await saveScreenshot('browser-qa-portal-desktop.png', 1440, 1000);
  await saveScreenshot('browser-qa-portal-mobile.png', 390, 1200);
  const layout = await getResponsiveLayoutSnapshot();
  const auditIsScrollable = layout.audit
    && (layout.audit.overflowX === 'auto' || layout.audit.overflowX === 'scroll')
    && layout.audit.scrollWidth > layout.audit.clientWidth;
  if (layout.documentScrollWidth <= layout.viewportWidth + 1 && layout.clippedControls.length === 0 && auditIsScrollable) {
    pass('TC-018', 'Dashboard остается usable с длинными значениями', 'Desktop/mobile screenshots captured; page has no body horizontal overflow, controls stay inside viewport, and Audit is horizontally scrollable.', {
      screenshots: ['browser-qa-portal-desktop.png', 'browser-qa-portal-mobile.png'],
      layout,
    });
  } else {
    partial('TC-018', 'Dashboard остается usable с длинными значениями', 'Screenshots captured, but responsive layout needs manual review.', {
      screenshots: ['browser-qa-portal-desktop.png', 'browser-qa-portal-mobile.png'],
      layout,
    });
  }

  const summary = {
    total: results.length,
    pass: results.filter((item) => item.status === 'PASS').length,
    fail: results.filter((item) => item.status === 'FAIL').length,
    partial: results.filter((item) => item.status === 'PARTIAL').length,
  };
  const resultPath = path.join(qaDir, 'browser-qa-results.json');
  const reportPath = path.join(qaDir, 'browser-qa-report.md');
  await writeFile(resultPath, `${JSON.stringify({ baseUrl, dbPath, summary, artifacts, results }, null, 2)}\n`);
  const report = [
    '# Browser QA Report: Portal Token and Client Management',
    '',
    `Base URL: ${baseUrl}`,
    `Date: ${new Date().toISOString()}`,
    '',
    '## Summary',
    '',
    `- Total: ${summary.total}`,
    `- PASS: ${summary.pass}`,
    `- FAIL: ${summary.fail}`,
    `- PARTIAL: ${summary.partial}`,
    '',
    '## Results',
    '',
    ...results.map((item) => `- ${item.status} ${item.id}: ${item.name} — ${item.evidence}`),
    '',
    '## Artifacts',
    '',
    `- ${path.relative(rootDir, resultPath).replaceAll('\\', '/')}`,
    ...artifacts.map((artifact) => `- ${artifact}`),
    '',
  ].join('\n');
  await writeFile(reportPath, report);
  console.log(JSON.stringify({ summary, report: path.relative(rootDir, reportPath), result: path.relative(rootDir, resultPath), artifacts }, null, 2));
} finally {
  if (page) {
    page.close();
  }

  if (chrome) {
    chrome.kill();
  }
}
