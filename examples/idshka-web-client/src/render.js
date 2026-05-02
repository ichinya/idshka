export function renderHome({ user }) {
  return renderPage(user ? renderAuthenticated(user) : renderGuest());
}

export function renderError({ title = 'Ошибка', message, details = null }) {
  const detailsMarkup = details
    ? `<pre>${escapeHtml(JSON.stringify(details, null, 2))}</pre>`
    : '';

  return renderPage(`
    <main class="shell">
      <section class="panel">
        <h1>${escapeHtml(title)}</h1>
        <p>${escapeHtml(message)}</p>
        ${detailsMarkup}
        <a class="button secondary" href="/">Назад</a>
      </section>
    </main>
  `);
}

function renderGuest() {
  return `
    <main class="center">
      <form method="POST" action="/auth/idshka/redirect">
        <button class="button" type="submit">Войти через idshka</button>
      </form>
    </main>
  `;
}

function renderAuthenticated(user) {
  const raw = {
    authenticated_at: user.authenticatedAt,
    userinfo: user.userinfo,
    token: user.token,
  };

  return `
    <main class="shell">
      <form class="actions" method="POST" action="/logout">
        <button class="button secondary" type="submit">Выйти</button>
      </form>
      <pre aria-label="userinfo">${escapeHtml(JSON.stringify(raw, null, 2))}</pre>
    </main>
  `;
}

function renderPage(content) {
  return `<!doctype html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>idshka web client</title>
    <style>
      :root {
        color-scheme: light;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        background: #f6f7f9;
        color: #171717;
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        min-height: 100vh;
      }

      .center {
        display: grid;
        min-height: 100vh;
        place-items: center;
        padding: 24px;
      }

      .shell {
        margin: 0 auto;
        max-width: 960px;
        min-height: 100vh;
        padding: 24px;
      }

      .actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 16px;
      }

      .panel,
      pre {
        border: 1px solid #d7dce2;
        border-radius: 8px;
        background: #ffffff;
      }

      .panel {
        padding: 20px;
      }

      h1 {
        margin: 0 0 8px;
        font-size: 20px;
      }

      p {
        margin: 0 0 16px;
      }

      pre {
        margin: 0;
        overflow: auto;
        padding: 18px;
        white-space: pre-wrap;
        word-break: break-word;
      }

      .button {
        appearance: none;
        border: 0;
        border-radius: 8px;
        background: #0f766e;
        color: #ffffff;
        cursor: pointer;
        display: inline-flex;
        font: inherit;
        font-weight: 700;
        justify-content: center;
        line-height: 1.2;
        min-width: 160px;
        padding: 13px 18px;
        text-decoration: none;
      }

      .button:hover {
        background: #115e59;
      }

      .button.secondary {
        border: 1px solid #c8d0d8;
        background: #ffffff;
        color: #171717;
      }

      .button.secondary:hover {
        background: #eef2f5;
      }
    </style>
  </head>
  <body>
    ${content}
  </body>
</html>`;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');
}
