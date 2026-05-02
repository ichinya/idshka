import { readFileSync } from 'node:fs';
import https from 'node:https';

import { createApp } from './app.js';
import { loadConfig } from './config.js';

const config = loadConfig();
const app = createApp({ config });

const server = https.createServer({
  cert: readFileSync(config.httpsCertPath),
  key: readFileSync(config.httpsKeyPath),
}, app);

server.listen(config.port, '0.0.0.0', () => {
  console.log(`idshka API token inspector listening on https://localhost:${config.port}`);
  console.log(`JWKS URL: ${config.jwksUrl}`);
});
