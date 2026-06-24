#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

template_path="${1:-${SAAS_KIT_TEMPLATE_PATH:-}}"
stub_path="stubs/root"

if [[ -z "$template_path" ]]; then
    echo "Usage: $0 /path/to/source-template" >&2
    echo "Or set SAAS_KIT_TEMPLATE_PATH=/path/to/source-template." >&2
    exit 2
fi

if [[ ! -d "$template_path" ]]; then
    echo "Template path does not exist: $template_path" >&2
    exit 2
fi

if [[ ! -d "$stub_path" ]]; then
    echo "SaaS Kit stub path does not exist: $stub_path" >&2
    exit 2
fi

diff -qr \
    -x '.git' \
    -x 'vendor' \
    -x 'node_modules' \
    -x 'bootstrap/cache' \
    -x 'packages.php' \
    -x 'services.php' \
    -x 'storage' \
    -x 'composer.lock' \
    -x '.env' \
    -x '.phpunit.result.cache' \
    -x '.phpunit.cache' \
    -x 'build' \
    -x 'public/build' \
    -x 'public/storage' \
    "$template_path" "$stub_path"
