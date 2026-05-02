# idshka Web Client Design

## Goal

Build a runnable HTTPS demo web client that lets a user log in through idshka and then shows the raw user data returned by `/oauth/userinfo`.

## Approach

The client lives in `examples/idshka-web-client` as a small Node.js/Express app. It runs behind HTTPS inside its own Docker container and receives all idshka integration settings through environment variables:

- `IDSHKA_PUBLIC_URL`: browser-facing issuer URL used for `/oauth/authorize`.
- `IDSHKA_INTERNAL_URL`: container-facing issuer URL used for `/oauth/token` and `/oauth/userinfo`.
- `IDSHKA_CLIENT_ID`: OIDC client id from the idshka portal.
- `IDSHKA_CLIENT_SECRET`: OIDC client secret from the idshka portal.
- `IDSHKA_REDIRECT_URI`: exact registered callback URI.

## Flow

The home page renders a single login button when no local session exists. The login route generates `state`, `nonce`, and a PKCE S256 verifier/challenge, stores the transient values in the local session, and redirects the browser to idshka.

The callback route validates `state`, exchanges the authorization code for tokens with the configured client credentials and PKCE verifier, calls `/oauth/userinfo` with the returned access token, stores only userinfo and non-secret token metadata in the local session, clears transient OAuth state, and redirects back to the home page.

After login, the home page renders the raw userinfo payload and a logout button. Logout destroys the local demo session.

## Docker

`compose.yml` gets a new `examples` profile service named `idshka-web-client`. It exposes `https://localhost:8443`, connects to the same Docker network as the issuer, and generates a self-signed localhost certificate at container start.

## Testing

Node tests cover:

- authorize URL generation, PKCE challenge generation, and session state storage;
- callback state mismatch rejection;
- token exchange plus userinfo fetch without returning raw tokens to the page model;
- logged-out and logged-in HTML states.
