#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

smoke_root="${SAAS_KIT_SMOKE_ROOT:-/tmp/laravel-saas-kit-post2site-smoke-$(date +%s)}"
port="${SAAS_KIT_SMOKE_PORT:-18890}"
log="${SAAS_KIT_SMOKE_LOG:-/tmp/laravel-saas-kit-post2site-smoke.log}"
kit_path="$(pwd)"
post2site_path="${POST2SITE_PATH:-../laravel-post2site}"
if [[ -d "$post2site_path" ]]; then
    post2site_path="$(cd "$post2site_path" && pwd)"
fi

rm -rf "$smoke_root"

composer create-project laravel/laravel:^12.0 "$smoke_root" --no-interaction >"$log" 2>&1
cd "$smoke_root"

composer config repositories.laravel-saas-kit path "$kit_path"
composer require n2ns/laravel-saas-kit:*@dev --no-interaction >>"$log" 2>&1

php artisan saas-kit:install --force >>"$log" 2>&1
cp .env.example .env
cat >> .env <<'ENV'
POST2SITE_PRESET=laravel_saas_kit
POST2SITE_PUBLISHING_MODE=adapter
POST2SITE_SAAS_KIT_AUTHOR_EMAIL=author@example.com
SAAS_KIT_LEGACY_MCP_ROUTES=false
ENV

php artisan key:generate >>"$log" 2>&1
touch database/database.sqlite
php artisan migrate:fresh --force >>"$log" 2>&1
php artisan db:seed --class=ReferenceDataSeeder --force >>"$log" 2>&1
php artisan passport:keys --force >>"$log" 2>&1
php artisan tinker --execute="App\\Models\\User::query()->updateOrCreate(['email' => 'author@example.com'], ['name' => 'Post2Site Author', 'password' => bcrypt('password')]);" >>"$log" 2>&1

if [[ -d "$post2site_path" ]]; then
    composer config repositories.laravel-post2site path "$post2site_path"
    composer require n2ns/laravel-post2site:*@dev --no-interaction >>"$log" 2>&1
else
    composer require n2ns/laravel-post2site:^0.2 --no-interaction >>"$log" 2>&1
fi

php artisan optimize:clear >>"$log" 2>&1
php artisan migrate --force >>"$log" 2>&1

api_key="$(php artisan post2site:key "Smoke" --plain)"

php artisan serve --host=127.0.0.1 --port="$port" >>"$log" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

for i in {1..30}; do
    if curl -fsS "http://127.0.0.1:$port/up" >/dev/null 2>&1; then
        break
    fi

    sleep 1

    if [[ "$i" == "30" ]]; then
        echo "Smoke server did not start. Log: $log" >&2
        tail -n 120 "$log" >&2
        exit 1
    fi
done

curl -fsS -H "X-API-KEY: $api_key" "http://127.0.0.1:$port/api/v1/mcp/capabilities" >/tmp/post2site-capabilities.json

blog_created="$(curl -fsS -H "X-API-KEY: $api_key" -H 'Content-Type: application/json' \
    -d '{"type":"technical","slug":"smoke-blog","locale":"en","title":"Smoke Blog","excerpt":"Smoke excerpt","content":"Smoke content"}' \
    "http://127.0.0.1:$port/api/v1/mcp/posts")"
blog_id="$(printf '%s' "$blog_created" | php -r '$j=json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR); echo $j["blog_post"]["id"];')"
curl -fsS -H "X-API-KEY: $api_key" -X POST "http://127.0.0.1:$port/api/v1/mcp/posts/$blog_id/publish" >/tmp/post2site-blog-published.json

guide_created="$(curl -fsS -H "X-API-KEY: $api_key" -H 'Content-Type: application/json' \
    -d '{"type":"guide","content_scope":"product:starter","slug":"smoke-guide","locale":"en","title":"Smoke Guide","excerpt":"Smoke guide excerpt","content":"Smoke guide content"}' \
    "http://127.0.0.1:$port/api/v1/mcp/posts")"
guide_id="$(printf '%s' "$guide_created" | php -r '$j=json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR); echo $j["blog_post"]["id"];')"
curl -fsS -H "X-API-KEY: $api_key" -X POST "http://127.0.0.1:$port/api/v1/mcp/posts/$guide_id/publish" >/tmp/post2site-guide-published.json

php -r '
foreach (["/tmp/post2site-blog-published.json", "/tmp/post2site-guide-published.json"] as $file) {
    $json = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    if (($json["blog_post"]["status"] ?? null) !== "published") {
        fwrite(STDERR, "Not published: {$file}\n");
        exit(1);
    }

    echo $json["blog_post"]["slug"]." => ".$json["blog_post"]["link"].PHP_EOL;
}
'

printf 'POST2SITE_SMOKE_OK=%s\n' "$smoke_root"
