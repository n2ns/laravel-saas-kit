# Post2Site Integration

SaaS Kit includes its own blog and product article system:

- public blog index: `/blog`
- public blog detail: `/blog/{slug}`
- product guide index: `/{productCode}/guides`
- product guide detail: `/{productCode}/guides/{slug}`
- storage tables: `blog_posts` and `blog_post_translations`
- admin resources for blog posts, product articles, and translations

For manual publishing, use the generated site's Filament admin panel.

For automated publishing from the Post2Site MCP client, install `n2ns/laravel-post2site` into the generated product site and use its SaaS Kit preset. No other blog package is required for SaaS Kit sites.

```bash
composer require n2ns/laravel-post2site
php artisan vendor:publish --tag=post2site-config
php artisan migrate
php artisan post2site:key "Production MCP"
```

```env
POST2SITE_PRESET=laravel_saas_kit
POST2SITE_PUBLISHING_MODE=adapter
POST2SITE_SAAS_KIT_AUTHOR_ID=1
```

Behavior:

- unscoped posts publish as normal blog posts at `/blog/{slug}`
- `type=guide` requires `content_scope=product:{code}`
- product guides publish at `/{code}/guides/{slug}`
- `product:{code}` must match an active product in the site database
- translations are written to `blog_post_translations`

Route note: both the generated template and Post2Site use `/api/v1/mcp` as the publishing API convention. New sites should standardize on `n2ns/laravel-post2site` for that API when automated publishing is needed. The template's older built-in MCP routes are behind this disabled-by-default switch:

```env
SAAS_KIT_LEGACY_MCP_ROUTES=false
```

Do not enable `SAAS_KIT_LEGACY_MCP_ROUTES` when `n2ns/laravel-post2site` is installed on the same route prefix.

Other Post2Site blog targets belong to the Post2Site package documentation. The generated SaaS Kit site should publish through the built-in SaaS Kit content model.
