import express from 'express';
import session from 'express-session';

import {
  buildAuthorizationRedirect,
  exchangeCodeForUserInfo,
  verifyReturnedState,
} from './oauth.js';
import { renderError, renderHome } from './render.js';

const SESSION_COOKIE_NAME = 'idshka_demo_session';

export function createApp({ config, fetchImpl = globalThis.fetch }) {
  const app = express();

  app.disable('x-powered-by');
  app.use(express.urlencoded({ extended: false }));
  app.use(session({
    name: SESSION_COOKIE_NAME,
    secret: config.sessionSecret,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      maxAge: 2 * 60 * 60 * 1000,
      sameSite: 'lax',
      secure: config.secureCookies,
    },
  }));

  app.get('/health', (request, response) => {
    response.json({ ok: true });
  });

  app.get('/', (request, response) => {
    if (hasMissingConfig(config)) {
      response.status(500).send(renderMissingConfig(config));

      return;
    }

    response.send(renderHome({ user: request.session.user ?? null }));
  });

  app.post('/auth/idshka/redirect', async (request, response, next) => {
    if (hasMissingConfig(config)) {
      response.status(500).send(renderMissingConfig(config));

      return;
    }

    try {
      const redirectUrl = buildAuthorizationRedirect(config, request.session);
      await saveSession(request);
      response.redirect(redirectUrl);
    } catch (error) {
      next(error);
    }
  });

  app.get('/auth/idshka/callback', async (request, response) => {
    try {
      if (request.query.error) {
        throw oauthErrorFromQuery(request.query);
      }

      const oauthState = request.session.idshkaOAuth;

      if (!oauthState) {
        throw new Error('OAuth session state is missing.');
      }

      verifyReturnedState(oauthState.state, String(request.query.state || ''));

      const result = await exchangeCodeForUserInfo(config, {
        code: String(request.query.code || ''),
        codeVerifier: oauthState.codeVerifier,
        fetchImpl,
      });

      request.session.user = {
        authenticatedAt: new Date().toISOString(),
        ...result,
      };
      delete request.session.idshkaOAuth;

      await saveSession(request);
      response.redirect('/');
    } catch (error) {
      delete request.session.idshkaOAuth;
      await saveSession(request).catch(() => {});
      response.status(statusForError(error)).send(renderError({
        title: 'Ошибка входа',
        message: error.message,
        details: safeErrorDetails(error),
      }));
    }
  });

  app.post('/logout', (request, response, next) => {
    request.session.destroy((error) => {
      if (error) {
        next(error);

        return;
      }

      response.clearCookie(SESSION_COOKIE_NAME);
      response.redirect('/');
    });
  });

  app.use((request, response) => {
    response.status(404).send(renderError({
      title: '404',
      message: 'Страница не найдена.',
    }));
  });

  app.use((error, request, response, next) => {
    if (response.headersSent) {
      next(error);

      return;
    }

    response.status(500).send(renderError({
      title: 'Ошибка',
      message: error.message || 'Unexpected server error.',
    }));
  });

  return app;
}

function hasMissingConfig(config) {
  return missingConfig(config).length > 0;
}

function renderMissingConfig(config) {
  return renderError({
    title: 'Не настроен клиент',
    message: 'Передайте IDSHKA_CLIENT_ID и IDSHKA_CLIENT_SECRET в окружение контейнера.',
    details: {
      missing: missingConfig(config),
      redirect_uri: config.redirectUri,
    },
  });
}

function missingConfig(config) {
  return config.missing ?? [];
}

function oauthErrorFromQuery(query) {
  const error = new Error(String(query.error_description || query.error));
  error.status = 400;
  error.payload = {
    error: query.error,
    error_description: query.error_description,
  };

  return error;
}

function statusForError(error) {
  return Number.isInteger(error.status) ? error.status : 400;
}

function safeErrorDetails(error) {
  if (!error.payload) {
    return null;
  }

  return {
    status: error.status,
    payload: error.payload,
  };
}

function saveSession(request) {
  return new Promise((resolve, reject) => {
    request.session.save((error) => {
      if (error) {
        reject(error);

        return;
      }

      resolve();
    });
  });
}
