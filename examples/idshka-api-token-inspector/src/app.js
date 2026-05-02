import express from 'express';

import { renderError, renderHome } from './render.js';
import { inspectToken } from './tokenInspector.js';

export function createApp({ config }) {
  const app = express();

  app.disable('x-powered-by');
  app.use(express.urlencoded({ extended: false, limit: '64kb' }));

  app.get('/health', (request, response) => {
    response.json({ ok: true });
  });

  app.get('/', (request, response) => {
    response.send(renderHome());
  });

  app.post('/inspect', async (request, response, next) => {
    try {
      const result = await inspectToken(request.body.token, config);

      response.send(renderHome({ result }));
    } catch (error) {
      next(error);
    }
  });

  app.use((request, response) => {
    response.status(404).send(renderError({
      title: '404',
      message: 'Page not found.',
    }));
  });

  app.use((error, request, response, next) => {
    if (response.headersSent) {
      next(error);

      return;
    }

    response.status(500).send(renderError({
      title: 'Error',
      message: error.message || 'Unexpected server error.',
    }));
  });

  return app;
}
