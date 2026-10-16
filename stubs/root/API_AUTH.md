# API Authentication

This site supports these authentication paths:

- **Web login** through Laravel Socialite at `/auth/google`, with the callback
  at `/auth/google/callback`.
- **Google One Tap** for guests, posting to `/auth/google/one-tap`.
- **External-client API login** through `POST /api/v1/auth/google`, which
  exchanges a Google ID token for Passport access and refresh tokens.
- **One-time-token (OTT) web login**, which lets an API-authenticated client
  open a logged-in web session.
- **API keys** in the `X-Api-Key` header for the blog management API.

External clients include browser extensions, mobile apps, desktop apps, IDE
extensions, and any frontend that obtains a Google ID token itself.

## First Setup

Run migrations and create Passport keys:

```bash
php artisan migrate --force
php artisan passport:keys
```

Create the confidential Passport client used by API Google login:

```bash
php artisan passport:ensure-social-client --create
```

The command prints the values to copy into `.env`:

```dotenv
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

Then rebuild cached configuration:

```bash
php artisan optimize:clear
php artisan optimize
```

Without `--create`, `passport:ensure-social-client` fails when the client ID
or secret is missing from `.env` or the client row does not exist, and
otherwise sets the client's `grant_types` to:

```json
["social","refresh_token"]
```

`scripts/deploy-production.sh` runs `php artisan passport:ensure-social-client`
on every deployment.

Passport keys can also be provided through `PASSPORT_PRIVATE_KEY` and
`PASSPORT_PUBLIC_KEY`.

## Login Flow

1. The external client obtains a Google ID token.
2. It calls `POST /api/v1/auth/google`.
3. `GoogleAuthRequest` validates the request: `client_id` must be a key in
   `config/auth_clients.php` `clients` (or `web`), and `product_code` must
   match that client's `product_code`.
4. `ApiAuthController` posts to Passport's `/oauth/token` on `APP_URL` with
   `grant_type=social`, the configured Passport client, and the scopes from
   `AuthClientRegistry::scopesFor()`.
5. `SocialGrant` verifies the ID token through `SocialAuthService`, which
   checks it against `GOOGLE_CLIENT_ID`, requires a verified email, and finds or
   creates the user and its `oauth_accounts` row. Banned users are rejected.
6. The controller creates or reuses a device session and returns the tokens.

Because the controller calls `APP_URL/oauth/token` over HTTP, `APP_URL` must be
reachable from the server itself.

## API Contract

### Login

```http
POST /api/v1/auth/google
Accept: application/json
Content-Type: application/json
```

```json
{
  "id_token": "<google-id-token>",
  "client_id": "chrome_starter",
  "product_code": "starter",
  "device_id": "device-or-installation-id",
  "device_name": "Optional device name",
  "platform": "chrome-extension",
  "app_version": "1.0.0",
  "locale": "en"
}
```

Required: `id_token` (at least 100 characters), `client_id`, `product_code`,
`device_id`, and `platform`. `device_name`, `app_version`, and `locale` are
optional.

### Refresh

```http
POST /api/v1/auth/refresh
Accept: application/json
Content-Type: application/json
```

```json
{
  "refresh_token": "<refresh-token>",
  "client_id": "chrome_starter",
  "product_code": "starter",
  "device_id": "device-or-installation-id",
  "device_name": "Optional device name",
  "platform": "chrome-extension",
  "app_version": "1.0.0"
}
```

Required: `refresh_token`, `client_id`, `product_code`, and `device_id`. The
refresh token must belong to a session with the same `client_id`,
`product_code`, and `device_id`; otherwise the refresh token is revoked and the
request is rejected.

### Token Response

```json
{
  "success": true,
  "token": "<access-token>",
  "refresh_token": "<refresh-token>",
  "token_type": "Bearer",
  "expires_in": 21600,
  "session": { "sid": "<session-uuid>" },
  "user": { "id": 1, "name": "...", "email": "...", "avatar": "...", "email_verified": true, "plan": "free", "status": "active", "auth_epoch": 1, "created_at": "..." },
  "subscriptions": []
}
```

- Access tokens expire after 6 hours and refresh tokens after 3 months
  (`AppServiceProvider`). Refresh tokens are not revoked after use.
- `plan` is `free`, `pro`, or `lifetime`, derived from active `pro` or
  `enterprise` tier subscriptions.
- `subscriptions` lists active, trialing, and past-due subscriptions that have
  not ended.

Access tokens are RS256 JWTs. Besides the standard claims and `scopes`, they
carry `plan`, `status`, `auth_epoch`, `name`, `email`, and `avatar` (when
set). The public key is published as a JWK Set at `GET /.well-known/jwks.json`
(from `PASSPORT_PUBLIC_KEY` or `storage/oauth-public.key`, cached for one
hour).

### Protected Endpoints

Send `Authorization: Bearer <token>`. These routes use the middleware
`auth:api`, `CheckToken::using('api')`, `auth.epoch`, and `auth.session`:

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/v1/auth/me` | Current user and active subscriptions. |
| `POST` | `/api/v1/auth/logout` | Revoke the current device session and its tokens. |
| `GET` | `/api/v1/auth/sessions` | List the user's unrevoked device sessions. |
| `POST` | `/api/v1/auth/sessions/revoke` | Revoke sessions by `session_ids`, `product_code`, or `client_id`; the current session is kept unless `include_current` is true. |
| `DELETE` | `/api/v1/auth/sessions/{session}` | Revoke one session by ID. |
| `POST` | `/api/v1/auth/ott` | Create a one-time web login token. |
| `GET` | `/api/v1/license/{product}` | Active subscriptions for one product code. |
| `GET` | `/api/v1/subscriptions` | All active subscriptions. |
| `GET` | `/api/v1/orders` | Paginated orders (`per_page`, default 20). |
| `POST` | `/api/v1/product-usage/{client}/track` | Queue a product usage event. |
| `GET` | `/api/v1/product-usage/{client}/daily` | Daily usage stats for the user. |
| `GET` | `/api/v1/product-usage/{client}/summary` | Usage summary between `start` and `end`. |
| `GET` | `/api/v1/product-usage/{client}/events` | Allowed event types for the client. |

Public endpoints: `GET /api/health` and
`GET /api/v1/products/{product}/config` (active plans and pricing URL; accepts
`?locale=`).

## Device Sessions and Revocation

- Each login creates or reuses one `user_sessions` row per user, `client_id`,
  `product_code`, and device. The device ID is stored only as an HMAC-SHA256
  hash keyed with `APP_KEY`.
- `user_session_tokens` links each issued access token and refresh token to
  its session. `auth.session` (`EnsureActiveApiSession`) rejects tokens whose
  session is missing or revoked, and rejects banned users.
- `auth.epoch` (`EnsureAuthEpoch`) rejects tokens whose `auth_epoch` claim
  differs from `users.auth_epoch`. The epoch is incremented when a checkout is
  fulfilled, a subscription is updated or deleted, a renewal invoice is paid,
  or a full refund cancels a subscription. Admins can also edit it in
  Filament.
- Admins can revoke sessions from **User Management > User Sessions** or the
  sessions tab on a user.

## One-Time Token Web Login

`POST /api/v1/auth/ott` accepts an optional `redirect_to` internal path and
returns `token`, `expires_in` (60 seconds), and `url`. Opening
`GET /ott/login?token=...` in a browser logs the user into a web session once
and redirects to `redirect_to`, or to the dashboard when the path is missing or
not a safe internal path.

## API Keys

The blog management API under `/api/v1/blog` uses the `api_key` middleware
(`AuthenticateApiKey`):

- Send the key in the `X-Api-Key` header.
- Keys are created in Filament under **System Configuration > API Key**. The
  form proposes a `df_` + 40 random character key and a one-year expiry.
- Keys are stored in plaintext so admins can view and copy them.
- Revoked or expired keys return `401`. The request is authenticated as the
  key's owner for that request only; no web session is created.

See [BLOG_GUIDELINES.md](BLOG_GUIDELINES.md) for the blog API payload.

## Client Configuration

`config/auth_clients.php` defines the external clients:

| `client_id` | `product_code` | `token_name` | Redirect setting |
|---|---|---|---|
| `web_starter` | `starter` | `web:starter` | `APP_URL/auth/google/callback` |
| `chrome_starter` | `starter` | `ext:starter` | `PRODUCT_KIT_CHROME_REDIRECT_URI` |
| `app_mobile` | `starter` | `app:mobile` | `PRODUCT_KIT_MOBILE_REDIRECT_URI` |

`web` is always accepted as a `client_id` and has no product restriction.
Scopes come from `client_scopes` keyed by `token_name`, defaulting to `api`.
The `redirect` values document each client's Google redirect URI; the server
does not read them.

When adding or changing an external client, keep these aligned:

- The client's `client_id` and `product_code`.
- The Google OAuth client used to mint ID tokens and its authorized redirect
  URI in Google Cloud.
- The client entry and `client_scopes` in `config/auth_clients.php`.
- Scope registration in `Passport::tokensCan()` in `AppServiceProvider`.
- Route middleware that checks the scope (`CheckToken::using('api')`).

Google ID tokens are verified against `GOOGLE_CLIENT_ID`. External clients must
mint ID tokens for that Google OAuth client unless the site adds per-client
audience support.

Browser-based clients that call `api/*` directly need their origin in
`CORS_ALLOWED_ORIGINS`.

## Rate Limits

Defined in `AppServiceProvider`, per IP address:

| Limiter | Applies to | Limit per minute |
|---|---|---|
| `api-auth` | `POST /api/v1/auth/google` | 20 |
| `api-refresh` | `POST /api/v1/auth/refresh` | 60 |
| `web-auth` | `/auth/google`, `/auth/google/one-tap` | 10 |
| `web-auth-callback` | `/auth/google/callback` | 30 |
| `ott-login` | `/ott/login` | 20 |
| `product-usage-track` | `product-usage/{client}/track` | 60 |
| `product-usage-read` | `product-usage/{client}/daily`, `summary`, `events` | 30 |

## Error Codes

| Response | Cause |
|---|---|
| `422` with `Unknown client identifier.` | `client_id` is not configured in `config/auth_clients.php`. |
| `422` with `Product code is not allowed for this client.` | `product_code` differs from the client's configured `product_code`. |
| `AUTH_FAILED` | Passport rejected the login. Google ID token verification failed (audience, expiry, unverified email, banned user), or the Passport client does not allow the `social` grant. |
| `CLIENT_ERROR` | Passport returned `invalid_client`: check `PASSPORT_PASSWORD_CLIENT_ID` and `PASSPORT_PASSWORD_CLIENT_SECRET`. |
| `REFRESH_TOKEN_EXPIRED` (401) | The refresh token is invalid, expired, or revoked. |
| `SESSION_NOT_FOUND` / `SESSION_REVOKED` / `SESSION_MISMATCH` (401) | The device session is missing, revoked, or does not match the refresh request. |
| `AUTH_EPOCH_STALE` (401) | The access token was issued before the user's `auth_epoch` changed. Refresh or sign in again. |
| `USER_BANNED` (403) | The user has `banned_at` set. |
| `UNKNOWN_CLIENT` (400) | The product usage `{client}` is not in `config/product_usage.php`. |

When `APP_DEBUG=true`, failed login and refresh responses include Passport's
raw error in a `debug` field (for example `unauthorized_client` or
`invalid_scope`). Run `php artisan passport:ensure-social-client` for
`unauthorized_client`; register missing scopes in `Passport::tokensCan()` for
`invalid_scope`.

A Google `redirect_uri_mismatch` error comes from Google before the site is
called: register the external client's redirect URI in Google Cloud.
