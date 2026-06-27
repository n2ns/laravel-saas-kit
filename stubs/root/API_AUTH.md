# API Google Authentication

This site supports two Google login paths:

- Web login through Laravel Socialite at `/auth/google`.
- External-client API login through `POST /api/v1/auth/google`.

External clients include browser extensions, mobile apps, desktop apps, IDE
extensions, and any frontend that obtains a Google ID token itself and exchanges
it for Passport API tokens.

## First Setup

Run migrations and Passport keys first:

```bash
php artisan migrate --force
php artisan passport:keys
```

Create the confidential Passport client used by API Google login:

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

Routine deployments run `php artisan passport:ensure-social-client`
automatically to keep the configured Passport client on:

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

## External Client Checklist

When adding or changing an external client, keep these values aligned:

- External client API `client_id`.
- External client `product_code`.
- Google OAuth client ID used by the external client.
- Google Cloud authorized redirect URI for the external client.
- `config/auth_clients.php` client entry.
- Scopes in `config/auth_clients.php`.
- Matching scope registration in `Passport::tokensCan()`.

By default, Google ID tokens are verified against `GOOGLE_CLIENT_ID`. External
clients must mint ID tokens for that configured Google OAuth client unless this
site intentionally adds per-client audience support.

If a browser-based client calls `api/*` directly, add its origin to
`CORS_ALLOWED_ORIGINS`.

Default clients receive the `api` scope. If you add scopes, update
`Passport::tokensCan()`, `config/auth_clients.php`, and route middleware
together.

## Common Failures

- Google `redirect_uri_mismatch`: the external client's redirect URI is not
  registered in Google Cloud.
- Laravel `422 Unknown client identifier`: `client_id` is not configured in
  `config/auth_clients.php`.
- Passport `unauthorized_client`: the Passport client does not allow the custom
  `social` grant. Run `php artisan passport:ensure-social-client`.
- Passport `invalid_scope`: a requested API scope is not registered in
  `Passport::tokensCan()`.
- API `AUTH_FAILED`: Google ID token verification failed. Check token audience,
  expiry, and whether the installed external-client build is current.
