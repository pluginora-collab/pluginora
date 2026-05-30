#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

WP_DEVELOP_DIR="${WP_DEVELOP_DIR:-${HOME}/wordpress-develop}"
WP_TESTS_DIR="${WP_TESTS_DIR:-${WP_DEVELOP_DIR}/tests/phpunit}"
WP_TESTS_CONFIG="${WP_DEVELOP_DIR}/wp-tests-config.php"
WP_TESTS_CONFIG_SAMPLE="${WP_DEVELOP_DIR}/wp-tests-config-sample.php"
WC_PLUGIN_FILE="${PLUGINORA_WC_PLUGIN_FILE:-${WP_DEVELOP_DIR}/src/wp-content/plugins/woocommerce/woocommerce.php}"
DB_NAME="${WP_TESTS_DB_NAME:-pluginora_test}"
DB_USER="${WP_TESTS_DB_USER:-root}"
DB_PASSWORD="${WP_TESTS_DB_PASSWORD:-}"
DB_HOST="${WP_TESTS_DB_HOST:-127.0.0.1}"
WP_PHP_BINARY="${WP_PHP_BINARY:-$(command -v php || true)}"
MYSQL_BIN="${MYSQL_BIN:-$(command -v mysql || true)}"

require_command() {
    local command_name="$1"

    if ! command -v "${command_name}" >/dev/null 2>&1; then
        echo "Missing required command: ${command_name}" >&2
        exit 1
    fi
}

if [[ -z "${WP_PHP_BINARY}" ]]; then
    echo "Unable to locate php. Install PHP 8.1+ before running this script." >&2
    exit 1
fi

require_command git
require_command curl
require_command unzip

if [[ -z "${MYSQL_BIN}" ]]; then
    echo "Unable to locate mysql. Install MySQL client/server before running this script." >&2
    exit 1
fi

echo "Using WordPress develop checkout: ${WP_DEVELOP_DIR}"

if [[ ! -d "${WP_DEVELOP_DIR}/.git" ]]; then
    git clone --depth=1 https://github.com/WordPress/wordpress-develop.git "${WP_DEVELOP_DIR}"
fi

if [[ ! -f "${WP_TESTS_DIR}/includes/bootstrap.php" ]]; then
    echo "WordPress tests bootstrap not found at ${WP_TESTS_DIR}." >&2
    exit 1
fi

echo "Ensuring test database exists: ${DB_NAME}"
"${MYSQL_BIN}" -u"${DB_USER}" ${DB_PASSWORD:+-p"${DB_PASSWORD}"} -h"${DB_HOST}" \
    -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [[ ! -f "${WP_TESTS_CONFIG}" ]]; then
    cp "${WP_TESTS_CONFIG_SAMPLE}" "${WP_TESTS_CONFIG}"
fi

PLUGINORA_WP_TESTS_CONFIG="${WP_TESTS_CONFIG}" \
PLUGINORA_WP_TESTS_DB_NAME="${DB_NAME}" \
PLUGINORA_WP_TESTS_DB_USER="${DB_USER}" \
PLUGINORA_WP_TESTS_DB_PASSWORD="${DB_PASSWORD}" \
PLUGINORA_WP_TESTS_DB_HOST="${DB_HOST}" \
PLUGINORA_WP_TESTS_PHP_BINARY="${WP_PHP_BINARY}" \
"${WP_PHP_BINARY}" <<'PHP'
<?php
$configPath = getenv('PLUGINORA_WP_TESTS_CONFIG') ?: '';
$contents = file_get_contents($configPath);
if ($contents === false) {
    fwrite(STDERR, "Failed to read wp-tests-config.php\n");
    exit(1);
}

$replacements = [
    "define( 'DB_NAME', 'youremptytestdbnamehere' );" => sprintf(
        "define( 'DB_NAME', '%s' );",
        getenv('PLUGINORA_WP_TESTS_DB_NAME') ?: 'pluginora_test'
    ),
    "define( 'DB_USER', 'yourusernamehere' );" => sprintf(
        "define( 'DB_USER', '%s' );",
        getenv('PLUGINORA_WP_TESTS_DB_USER') ?: 'root'
    ),
    "define( 'DB_PASSWORD', 'yourpasswordhere' );" => sprintf(
        "define( 'DB_PASSWORD', '%s' );",
        getenv('PLUGINORA_WP_TESTS_DB_PASSWORD') ?: ''
    ),
    "define( 'DB_HOST', 'localhost' );" => sprintf(
        "define( 'DB_HOST', '%s' );",
        getenv('PLUGINORA_WP_TESTS_DB_HOST') ?: '127.0.0.1'
    ),
    "define( 'WP_PHP_BINARY', 'php' );" => sprintf(
        "define( 'WP_PHP_BINARY', '%s' );",
        getenv('PLUGINORA_WP_TESTS_PHP_BINARY') ?: 'php'
    ),
];

$updated = strtr($contents, $replacements);
if ($updated !== $contents && file_put_contents($configPath, $updated) === false) {
    fwrite(STDERR, "Failed to update wp-tests-config.php\n");
    exit(1);
}
PHP

if [[ ! -f "${WC_PLUGIN_FILE}" ]]; then
    echo "Installing WooCommerce into ${WP_DEVELOP_DIR}/src/wp-content/plugins"
    mkdir -p "${WP_DEVELOP_DIR}/src/wp-content/plugins"
    curl -L https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip -o "${WP_DEVELOP_DIR}/src/wp-content/plugins/woocommerce.latest-stable.zip"
    unzip -oq "${WP_DEVELOP_DIR}/src/wp-content/plugins/woocommerce.latest-stable.zip" -d "${WP_DEVELOP_DIR}/src/wp-content/plugins"
    rm -f "${WP_DEVELOP_DIR}/src/wp-content/plugins/woocommerce.latest-stable.zip"
fi

if [[ ! -f "${WC_PLUGIN_FILE}" ]]; then
    echo "WooCommerce plugin file not found after installation: ${WC_PLUGIN_FILE}" >&2
    exit 1
fi

cat <<EOF
Integration test environment is ready.

Detected paths:
- WP_DEVELOP_DIR=${WP_DEVELOP_DIR}
- WP_TESTS_DIR=${WP_TESTS_DIR}
- PLUGINORA_WC_PLUGIN_FILE=${WC_PLUGIN_FILE}

Next command:
composer test:integration
EOF