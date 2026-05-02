export function renderHome({ result = null } = {}) {
  return page(`
    <section class="panel">
      <h1>API token inspector</h1>
      <form method="POST" action="/inspect">
        <label>
          <span>Bearer token</span>
          <textarea name="token" spellcheck="false" autocomplete="off" required></textarea>
        </label>
        <button type="submit">Inspect token</button>
      </form>
    </section>
    ${result ? renderResult(result) : ''}
  `);
}

export function renderError({ title = 'Error', message }) {
  return page(`
    <section class="panel panel-error">
      <h1>${escapeHtml(title)}</h1>
      <p>${escapeHtml(message)}</p>
      <a href="/">Back</a>
    </section>
  `);
}

function renderResult(result) {
  if (!result.ok) {
    return `
      <section class="panel panel-error">
        <h2>Token error</h2>
        <p>${escapeHtml(result.message)}</p>
      </section>
    `;
  }

  return `
    <section class="panel">
      <div class="result-header">
        <h2>Verification</h2>
        <span class="status status-${statusClass(result.verification.status)}">${escapeHtml(result.verification.status)}</span>
      </div>
      ${result.verification.status === 'failed' ? `<p class="error-text">${escapeHtml(result.verification.message)}</p>` : ''}
      ${renderSummary(result.summary)}
    </section>
    <section class="grid">
      <div class="panel">
        <h2>Header</h2>
        <pre>${escapeHtml(JSON.stringify(result.header, null, 2))}</pre>
      </div>
      <div class="panel">
        <h2>Payload</h2>
        <pre>${escapeHtml(JSON.stringify(result.payload, null, 2))}</pre>
      </div>
    </section>
  `;
}

function renderSummary(summary) {
  const rows = [
    ['Token type', summary.token_type],
    ['Subject', summary.subject],
    ['Site ID', summary.site_id],
    ['Issuer', summary.issuer],
    ['Audience', summary.audience.join(', ')],
    ['Key ID', summary.kid],
    ['Algorithm', summary.alg],
    ['JTI', summary.jti],
    ['Issued at', summary.issued_at],
    ['Not before', summary.not_before_at],
    ['Expires at', summary.does_not_expire ? 'Never' : summary.expires_at],
    ['Expires in', summary.does_not_expire ? 'Never' : `${summary.expires_in_seconds} seconds`],
    ['Scopes', summary.scopes.join(', ')],
    ['Permissions', summary.permissions.join(', ')],
  ];

  return `
    <dl class="summary">
      ${rows.map(([label, value]) => `
        <div>
          <dt>${escapeHtml(label)}</dt>
          <dd>${escapeHtml(value || '-')}</dd>
        </div>
      `).join('')}
    </dl>
  `;
}

function page(body) {
  return `<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>idshka API token inspector</title>
    <style>
      :root {
        color-scheme: light;
        --bg: #f7f8fa;
        --panel: #ffffff;
        --text: #111827;
        --muted: #5b6472;
        --border: #d9dee7;
        --accent: #087f8c;
        --error: #b42318;
        --ok: #067647;
        --warn: #b54708;
      }
      * { box-sizing: border-box; }
      body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }
      main {
        width: min(1120px, calc(100% - 32px));
        margin: 0 auto;
        padding: 24px 0 40px;
      }
      h1, h2 { margin: 0; line-height: 1.2; letter-spacing: 0; }
      h1 { font-size: 24px; }
      h2 { font-size: 18px; }
      .panel {
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--panel);
        padding: 16px;
      }
      .panel + .panel, .panel + .grid { margin-top: 16px; }
      .panel-error {
        border-color: #f3b4ae;
        background: #fff7f6;
      }
      form {
        display: grid;
        gap: 12px;
        margin-top: 14px;
      }
      label {
        display: grid;
        gap: 6px;
        font-size: 14px;
        font-weight: 600;
      }
      textarea {
        width: 100%;
        min-height: 150px;
        resize: vertical;
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 10px;
        font: 13px/1.45 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      }
      button {
        justify-self: start;
        border: 0;
        border-radius: 6px;
        background: var(--accent);
        color: white;
        padding: 10px 14px;
        font-weight: 700;
        cursor: pointer;
      }
      .grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
      }
      .result-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
      }
      .status {
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
      }
      .status-verified { background: #dcfae6; color: var(--ok); }
      .status-failed { background: #fee4e2; color: var(--error); }
      .status-neutral { background: #f2f4f7; color: var(--muted); }
      .error-text { color: var(--error); }
      .summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 18px;
        margin: 16px 0 0;
      }
      .summary div { min-width: 0; }
      dt {
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
      }
      dd {
        margin: 3px 0 0;
        overflow-wrap: anywhere;
        font-size: 14px;
      }
      pre {
        overflow: auto;
        max-height: 520px;
        margin: 12px 0 0;
        border-radius: 6px;
        background: #111827;
        color: #f9fafb;
        padding: 12px;
        font: 12px/1.45 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      }
      a { color: var(--accent); font-weight: 700; }
      @media (max-width: 760px) {
        .grid, .summary { grid-template-columns: 1fr; }
      }
    </style>
  </head>
  <body>
    <main>${body}</main>
  </body>
</html>`;
}

function statusClass(status) {
  if (status === 'verified') {
    return 'verified';
  }

  if (status === 'failed') {
    return 'failed';
  }

  return 'neutral';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
