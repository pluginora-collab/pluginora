#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
DIST_DIR="${DIST_DIR:-${PROJECT_ROOT}/dist}"
BUILD_ROOT="$(mktemp -d)"
STAGING_DIR="${BUILD_ROOT}/pluginora"

cleanup() {
    rm -rf "${BUILD_ROOT}"
}

trap cleanup EXIT

require_command() {
    local command_name="$1"

    if ! command -v "${command_name}" >/dev/null 2>&1; then
        echo "Missing required command: ${command_name}" >&2
        exit 1
    fi
}

require_command composer
require_command php
require_command rsync
require_command zip

VERSION="$(php -r '$contents = file_get_contents("pluginora.php"); if ($contents === false || ! preg_match("/^ \* Version: (.+)$/m", $contents, $matches)) { fwrite(STDERR, "Unable to determine plugin version\n"); exit(1);} echo trim($matches[1]);')"
ZIP_PATH="${DIST_DIR}/pluginora-${VERSION}.zip"
CHECKSUM_PATH="${ZIP_PATH}.sha256"

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

mkdir -p "${DIST_DIR}"
mkdir -p "${STAGING_DIR}"

echo "Building Pluginora release ${VERSION}"

rsync -a \
    --exclude='.git/' \
    --exclude='.github/' \
    --exclude='dist/' \
    --exclude='docs/' \
    --exclude='tests/' \
    --exclude='bin/' \
    --exclude='vendor/' \
    --exclude='config.md' \
    --exclude='.gitignore' \
    --exclude='.phpunit.result.cache' \
    --exclude='phpunit.xml.dist' \
    --exclude='phpunit.integration.xml.dist' \
    --exclude='phpcs.xml.dist' \
    --exclude='phpstan.neon.dist' \
    --exclude='phpstan-bootstrap.php' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='playwright.config.js' \
    --exclude='node_modules/' \
    --exclude='.DS_Store' \
    "${PROJECT_ROOT}/" \
    "${STAGING_DIR}/"

(
    cd "${STAGING_DIR}"
    composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --optimize-autoloader

    rm -f composer.json composer.lock
)

rm -f "${ZIP_PATH}"
rm -f "${CHECKSUM_PATH}"

(
    cd "${BUILD_ROOT}"
    zip -rq "${ZIP_PATH}" pluginora
)

CHECKSUM_COMMAND="$(resolve_checksum_command)"
CHECKSUM_VALUE="$(${CHECKSUM_COMMAND} "${ZIP_PATH}" | awk '{print $1}')"
printf '%s  %s\n' "${CHECKSUM_VALUE}" "$(basename "${ZIP_PATH}")" > "${CHECKSUM_PATH}"

echo "Release artifact created: ${ZIP_PATH}"
echo "Release checksum created: ${CHECKSUM_PATH}"