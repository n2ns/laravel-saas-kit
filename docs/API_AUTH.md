# API Google Authentication

SaaS Kit supports two Google login paths:

- Web login through Laravel Socialite at `/auth/google`.
- External-client API login through `POST /api/v1/auth/google`.

The second path is used by browser extensions, mobile apps, desktop apps, IDE
extensions, or any client that obtains a Google ID token itself and exchanges it
for this site's Passport access and refresh tokens.

## External-Client Request Flow

1. The external client starts Google OAuth with its own registered redirect URI.
2. Google returns an ID token to the client.
3. The client posts the ID token to `POST /api/v1/auth/google` with:
   - `id_token`
   - `client_id`
   - `product_code`
   - `device_id`
   - `platform`
4. `AuthClientRegistry` validates that `client_id` is known and allowed for the
   requested `product_code`.
5. The API proxies to `/oauth/token` with `grant_type=social`.
6. `SocialGrant` verifies the Google ID token and Passport issues API tokens.

## Required Setup

Run migrations and Passport keys first:

```bash
php artisan migrate --force
php artisan passport:keys
```

Create the initial confidential Passport client for API Google login:

```bash
php artisan passport:ensure-social-client --create
```

Copy the printed values into `.env`:

```dotenv
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

Then rebuild cached configuration:

```bash
php artisan optimize:clear
php artisan optimize
```

Routine deployments run this guardrail automatically:

```bash
php artisan passport:ensure-social-client
```

That command verifies that the configured Passport client has:

```json
["social","refresh_token"]
```

## API Contract

Login request:

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

Refresh request:

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

The default token response includes `token`, `refresh_token`, `token_type`,
`expires_in`, `session`, `user`, and `subscriptions`.

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
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `PASSPORT_PASSWORD_CLIENT_ID`
  - `PASSPORT_PASSWORD_CLIENT_SECRET`
- Laravel config:
  - `config/auth_clients.php`
  - scopes registered in `Passport::tokensCan()`
- Database:
  - `oauth_clients.id` matches `PASSPORT_PASSWORD_CLIENT_ID`
  - `oauth_clients.grant_types` is `["social","refresh_token"]`

By default, `SocialAuthService` verifies Google ID tokens against
`GOOGLE_CLIENT_ID`. External clients must mint ID tokens for that configured
Google OAuth client unless the host application intentionally adds per-client
audience support.

The default CORS config allows origins from `CORS_ALLOWED_ORIGINS`. Add any
browser-extension, hosted frontend, or web-app origin that calls `api/*`
directly from a browser.

## Scopes

Default clients receive the `api` scope through `config/auth_clients.php`, and
protected API routes require that scope. If a host application adds new scopes,
update all three places together:

- `Passport::tokensCan()` in `AppServiceProvider`.
- `config/auth_clients.php` `client_scopes`.
- Route middleware that checks scopes.

Unregistered scopes cause Passport `invalid_scope` errors.


## Common Failure Mapping

- Google `redirect_uri_mismatch`: the external client's redirect URI is not
  registered in Google Cloud.
- Laravel `422 Unknown client identifier`: `client_id` is missing from
  `config/auth_clients.php`.
- Passport `unauthorized_client`: the Passport client does not allow the custom
  `social` grant. Run `php artisan passport:ensure-social-client`.
- Passport `invalid_scope`: a scope returned by `AuthClientRegistry` is not
  registered in `Passport::tokensCan()`.
- API `AUTH_FAILED`: the request reached Google token verification. Check the
  Google ID token audience, expiry, and whether the installed client build is
  current.

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
