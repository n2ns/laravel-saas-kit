# API Google Authentication

SaaS Kit supports two Google login paths:

- Web login through Laravel Socialite at `/auth/google` (callback `/auth/google/callback`), plus Google One Tap at `POST /auth/google/one-tap`.
- External-client API login through `POST /api/v1/auth/google`.

The second path is used by browser extensions, mobile apps, desktop apps, IDE
extensions, or any client that obtains a Google ID token itself and exchanges it
for this site's Passport access and refresh tokens.

The installed site also gets a shorter copy of this guide as `API_AUTH.md`.

## Request Flow

1. The external client starts Google OAuth with its own registered redirect URI.
2. Google returns an ID token to the client.
3. The client posts the ID token to `POST /api/v1/auth/google`. Required fields:
   - `id_token`
   - `client_id`
   - `product_code`
   - `device_id`
   - `platform`

   Optional fields: `device_name`, `app_version`, `locale`.
4. `GoogleAuthRequest` validates the request. `client_id` must be one of
   `AuthClientRegistry::clientIds()`: the keys of `config/auth_clients.php`
   `clients`, plus the built-in `web`. If that client entry defines a
   `product_code`, the request's `product_code` must match it.
5. `ApiAuthController` sends an HTTP request to `APP_URL/oauth/token` with
   `grant_type=social`, the Passport client from `PASSPORT_PASSWORD_CLIENT_ID` /
   `PASSPORT_PASSWORD_CLIENT_SECRET`, and the scopes that `AuthClientRegistry`
   returns for the client. `APP_URL` must therefore reach this application from
   the server itself. In the `local` environment, TLS verification is skipped
   for this request.
6. `SocialGrant` (`app/Auth/Grants/SocialGrant.php`) verifies the Google ID token
   through `SocialAuthService`, finds or creates the user (the Google email must
   be verified), rejects banned users, and Passport issues the tokens.
7. The controller records a device session (`user_sessions`) keyed by
   `client_id`, `product_code`, and a hash of `device_id`, and returns the tokens.

Token lifetimes are set in `AppServiceProvider`: access tokens expire after 6
hours and refresh tokens after 3 months.

## Required Setup

Run the kit's migrations first (`php artisan migrate:fresh --force` on a new
site; see [Installation](INSTALLATION.md#why-migratefresh)). The Passport
`oauth_*` tables are part of the kit's identity migration. Then generate the
Passport keys:

```bash
php artisan passport:keys --force
```

The keys are written to `storage/`. Alternatively, `config/passport.php` reads
them from `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY`.

Create the initial confidential Passport client for API Google login:

```bash
php artisan passport:ensure-social-client --create
```

`--create` only creates a client when `PASSPORT_PASSWORD_CLIENT_ID` is empty. The
client is named `SaaS Kit API Social Grant Client` and uses the `users` provider;
you can change these with `--name` and `--provider`. Copy the printed values
into `.env`:

```dotenv
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

Then rebuild cached configuration:

```bash
php artisan optimize:clear
php artisan optimize
```

Routine deployments run this guardrail automatically (`scripts/deploy-production.sh`
in the installed site):

```bash
php artisan passport:ensure-social-client
```

Without `--create`, the command fails if the `oauth_clients` table is missing,
if `PASSPORT_PASSWORD_CLIENT_ID` or `PASSPORT_PASSWORD_CLIENT_SECRET` is empty, or
if the configured client does not exist. Otherwise it makes sure the client's
`grant_types` are exactly the following, and rewrites them if they differ:

```json
["social","refresh_token"]
```

## API Contract

All routes below are in the installed site's `routes/api.php` under the `api`
prefix.

Login request (rate limit `api-auth`, 20 per minute per IP):

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

Refresh request (rate limit `api-refresh`, 60 per minute per IP). `refresh_token`,
`client_id`, `product_code`, and `device_id` are required:

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

A refresh must come from the same session: `client_id`, `product_code`, and
`device_id` must match the device session the refresh token was issued to.

A successful login or refresh response includes `success`, `token`,
`refresh_token`, `token_type` (`Bearer`), `expires_in`, `session` (with `sid`),
`user`, and `subscriptions`.

Protected routes require `Authorization: Bearer <token>` and run the
`auth:api`, `CheckToken::using('api')`, `auth.epoch`, and `auth.session`
middleware:

- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/sessions`
- `POST /api/v1/auth/sessions/revoke`
- `DELETE /api/v1/auth/sessions/{session}`
- `POST /api/v1/auth/ott`, which returns a one-time `url` for `/ott/login` that
  signs the user in on the website
- `GET /api/v1/license/{product}`, `GET /api/v1/subscriptions`,
  `GET /api/v1/orders`, and the `/api/v1/product-usage/{client}/...` routes

`auth.epoch` rejects tokens issued before the user's auth epoch changed
(`AUTH_EPOCH_STALE`). `auth.session` rejects tokens whose device session is
missing or revoked (`SESSION_NOT_FOUND`, `SESSION_REVOKED`) or whose user is
banned (`USER_BANNED`).

## Configuration Alignment

These values must match whenever a new external client is added:

- The external client source code:
  - API `client_id`
  - `product_code`
  - Google OAuth client ID
  - Google redirect URI
- Google Cloud OAuth configuration:
  - authorized redirect URI for that client
- Laravel `.env`:
  - `APP_URL`
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `PASSPORT_PASSWORD_CLIENT_ID`
  - `PASSPORT_PASSWORD_CLIENT_SECRET`
- Laravel config:
  - `config/auth_clients.php`: a `clients` entry with `product_code` and
    `token_name`, and a `client_scopes` entry for that `token_name`
  - scopes registered in `Passport::tokensCan()`
- Database:
  - `oauth_clients.id` matches `PASSPORT_PASSWORD_CLIENT_ID`
  - `oauth_clients.grant_types` is `["social","refresh_token"]`

The default clients are `web_starter`, `chrome_starter`, and `app_mobile`, all
for product `starter`. Their redirect URIs come from `APP_URL`,
`PRODUCT_KIT_CHROME_REDIRECT_URI`, and `PRODUCT_KIT_MOBILE_REDIRECT_URI`.

Keep client IDs stable: issued sessions and refresh tokens are scoped to
`client_id`, `product_code`, and `device_id`.

By default, `SocialAuthService` verifies Google ID tokens against
`GOOGLE_CLIENT_ID`. External clients must mint ID tokens for that configured
Google OAuth client unless the host application intentionally adds per-client
audience support.

The default CORS config (`config/cors.php`) applies to `api/*` and
`auth/google/one-tap`, and allows the origins listed in the comma-separated
`CORS_ALLOWED_ORIGINS`. Add any browser-extension, hosted frontend, or web-app
origin that calls `api/*` directly from a browser.

## Scopes

`AuthClientRegistry::scopesFor()` maps a client's `token_name` to
`config/auth_clients.php` `client_scopes`, and falls back to `['api']`. The
default clients receive the `api` scope, which is the only scope registered in
`Passport::tokensCan()`. Protected API routes require it through
`CheckToken::using('api')`. If a host application adds new scopes, update all
three places together:

- `Passport::tokensCan()` in `AppServiceProvider`.
- `config/auth_clients.php` `client_scopes`.
- Route middleware that checks scopes.

Unregistered scopes cause Passport `invalid_scope` errors.

## Common Failure Mapping

The API maps Passport token errors to its own `error` codes: `invalid_client` →
`CLIENT_ERROR`, `invalid_grant` → `AUTH_FAILED` on login and
`REFRESH_TOKEN_EXPIRED` (HTTP 401) on refresh, and every other Passport error →
`AUTH_FAILED`. The original Passport error is only included in the response's
`debug` field when `APP_DEBUG=true`.

- Google `redirect_uri_mismatch`: the external client's redirect URI is not
  registered in Google Cloud.
- Laravel `422 Unknown client identifier`: `client_id` is missing from
  `config/auth_clients.php`.
- Laravel `422 Product code is not allowed for this client.`: `product_code` does
  not match the client's configured `product_code`.
- `CLIENT_ERROR` (Passport `invalid_client`): `PASSPORT_PASSWORD_CLIENT_ID` or
  `PASSPORT_PASSWORD_CLIENT_SECRET` is wrong, or the client is revoked. Check
  `.env` and rebuild the config cache.
- `AUTH_FAILED` with Passport `unauthorized_client`: the Passport client does not
  allow the custom `social` grant. Run `php artisan passport:ensure-social-client`.
- `AUTH_FAILED` with Passport `invalid_scope`: a scope returned by
  `AuthClientRegistry` is not registered in `Passport::tokensCan()`.
- `AUTH_FAILED` with Passport `invalid_grant` on login: Google ID token
  verification failed, the Google email is not verified, or the user is banned.
  Check the Google ID token audience and expiry, and whether the installed
  client build is current.
- Refresh `SESSION_NOT_FOUND`, `SESSION_REVOKED`, or `SESSION_MISMATCH` (HTTP 401):
  the refresh token does not belong to an active device session for this
  `client_id`, `product_code`, and `device_id`. The client must log in again.

## Other API Credentials

The bot blog API (`/api/v1/blog`) does not use Passport. It authenticates with
an `X-Api-Key` header through the `api_key` middleware. Manage these keys with
the "API Key" resource (`ApiKeyResource`) in the Filament admin panel.

## Verification Commands

Check the Passport client:

```bash
php artisan tinker --execute='
echo json_encode(DB::table("oauth_clients")
    ->where("id", config("passport.password_client.id"))
    ->select("id", "name", "grant_types", "revoked")
    ->first(), JSON_PRETTY_PRINT);
'
```

Check registered scopes:

```bash
php artisan tinker --execute='
echo json_encode(Laravel\Passport\Passport::scopes()->pluck("id")->values()->all(), JSON_PRETTY_PRINT);
'
```

Check configured API clients:

```bash
php artisan tinker --execute='
$registry = app(App\Services\Auth\AuthClientRegistry::class);
echo json_encode([
    "client_ids" => $registry->clientIds(),
], JSON_PRETTY_PRINT);
'
```

Run auth-related tests:

```bash
php artisan test tests/Feature/PassportSocialClientCommandTest.php
```
