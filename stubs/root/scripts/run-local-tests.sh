#!/usr/bin/env bash

# Run the local test suite with Laravel's parallel test runner.

TEST_PROCESSES="${TEST_PROCESSES:-4}"
TEST_RECREATE_DATABASES="${TEST_RECREATE_DATABASES:-1}"

args=(--parallel --processes="${TEST_PROCESSES}")

if [[ "${TEST_RECREATE_DATABASES}" != "0" ]]; then
    args+=(--recreate-databases)
fi

php artisan test "${args[@]}" "$@"
