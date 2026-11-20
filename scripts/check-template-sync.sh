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

# Exclusions are anchored to the template root. `diff -x` matches base names
# only, which also hid same-named files elsewhere (such as config/services.php).
list_files() {
    (
        cd "$1"
        find . \
            \( -path ./.git -o -path ./vendor -o -path ./node_modules -o -path ./storage \
               -o -path ./public/build -o -path ./public/storage -o -path ./.phpunit.cache \) -prune \
            -o \( -type f -o -type l \) \
            ! -path './bootstrap/cache/*.php' \
            ! -path ./composer.lock \
            ! -path ./.env \
            ! -path ./.phpunit.result.cache \
            -print
    ) | sed 's|^\./||' | LC_ALL=C sort
}

template_files="$(list_files "$template_path")"
stub_files="$(list_files "$stub_path")"
status=0

while IFS= read -r file; do
    [[ -z "$file" ]] && continue
    echo "Only in $template_path: $file"
    status=1
done < <(LC_ALL=C comm -23 <(printf '%s\n' "$template_files") <(printf '%s\n' "$stub_files"))

while IFS= read -r file; do
    [[ -z "$file" ]] && continue
    echo "Only in $stub_path: $file"
    status=1
done < <(LC_ALL=C comm -13 <(printf '%s\n' "$template_files") <(printf '%s\n' "$stub_files"))

while IFS= read -r file; do
    [[ -z "$file" ]] && continue
    if ! cmp -s "$template_path/$file" "$stub_path/$file"; then
        echo "Files $template_path/$file and $stub_path/$file differ"
        status=1
    fi
done < <(LC_ALL=C comm -12 <(printf '%s\n' "$template_files") <(printf '%s\n' "$stub_files"))

exit "$status"
