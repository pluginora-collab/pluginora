#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

require_command() {
    local command_name="$1"

    if ! command -v "${command_name}" >/dev/null 2>&1; then
        echo "Missing required command: ${command_name}" >&2
        exit 1
    fi
}

resolve_checksum_command() {
    if command -v shasum >/dev/null 2>&1; then
        echo "shasum -a 256"
        return
    fi

    if command -v sha256sum >/dev/null 2>&1; then
        echo "sha256sum"
        return
    fi

    echo "Missing required command: shasum or sha256sum" >&2
    exit 1
}

require_command composer
require_command php
require_command unzip

cd "${PROJECT_ROOT}"

echo "Running release preflight checks"
composer run lint:phpcs
composer run lint:phpstan
composer test:unit
composer test:integration
composer build:release

VERSION="$(php -r '$contents = file_get_contents("pluginora.php"); if ($contents === false || ! preg_match("/^ \* Version: (.+)$/m", $contents, $matches)) { fwrite(STDERR, "Unable to determine plugin version\n"); exit(1);} echo trim($matches[1]);')"
ZIP_PATH="${PROJECT_ROOT}/dist/pluginora-${VERSION}.zip"
CHECKSUM_PATH="${ZIP_PATH}.sha256"

if [[ ! -f "${ZIP_PATH}" ]]; then
    echo "Missing release zip: ${ZIP_PATH}" >&2
    exit 1
fi

if [[ ! -f "${CHECKSUM_PATH}" ]]; then
    echo "Missing checksum file: ${CHECKSUM_PATH}" >&2
    exit 1
fi

CHECKSUM_COMMAND="$(resolve_checksum_command)"
EXPECTED_CHECKSUM="$(awk '{print $1}' "${CHECKSUM_PATH}")"
ACTUAL_CHECKSUM="$(${CHECKSUM_COMMAND} "${ZIP_PATH}" | awk '{print $1}')"

if [[ "${EXPECTED_CHECKSUM}" != "${ACTUAL_CHECKSUM}" ]]; then
    echo "Checksum mismatch for ${ZIP_PATH}" >&2
    echo "Expected: ${EXPECTED_CHECKSUM}" >&2
    echo "Actual:   ${ACTUAL_CHECKSUM}" >&2
    exit 1
fi

ZIP_ENTRIES="$(unzip -Z1 "${ZIP_PATH}")"

for required_path in \
    "pluginora/pluginora.php" \
    "pluginora/readme.txt" \
    "pluginora/uninstall.php" \
    "pluginora/vendor/autoload.php"; do
    if ! grep -Fxq "${required_path}" <<<"${ZIP_ENTRIES}"; then
        echo "Missing required release entry: ${required_path}" >&2
        exit 1
    fi
done

for excluded_prefix in \
    "pluginora/tests/" \
    "pluginora/docs/" \
    "pluginora/.github/" \
    "pluginora/bin/"; do
    if grep -Fq "${excluded_prefix}" <<<"${ZIP_ENTRIES}"; then
        echo "Unexpected development-only content in release zip: ${excluded_prefix}" >&2
        exit 1
    fi
done

for excluded_entry in \
    "pluginora/config.md" \
    "pluginora/.gitignore" \
    "pluginora/phpstan.neon.dist" \
    "pluginora/phpstan-bootstrap.php" \
    "pluginora/package.json" \
    "pluginora/package-lock.json" \
    "pluginora/playwright.config.js"; do
    if grep -Fxq "${excluded_entry}" <<<"${ZIP_ENTRIES}"; then
        echo "Unexpected development-only file in release zip: ${excluded_entry}" >&2
        exit 1
    fi
done

echo "Release preflight passed"
echo "Artifact: ${ZIP_PATH}"
echo "Checksum: ${CHECKSUM_PATH}"