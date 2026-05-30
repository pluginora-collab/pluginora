<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

if (! file_exists($autoload)) {
    fwrite(STDERR, "Composer autoload file not found. Run composer install before running integration tests.\n");
    exit(1);
}

require_once $autoload;

$phpunitPolyfillsPath = dirname(__DIR__, 2) . '/vendor/yoast/phpunit-polyfills';

if (! defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH') && is_dir($phpunitPolyfillsPath)) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $phpunitPolyfillsPath);
}

$wpTestsDir = pluginora_find_wp_tests_dir();

if (! $wpTestsDir || ! is_dir($wpTestsDir)) {
    fwrite(
        STDERR,
        "Unable to locate the WordPress test suite. "
        . "Set WP_TESTS_DIR or install wordpress-develop in a standard local path.\n"
    );
    exit(1);
}

$pluginoraPluginFile = dirname(__DIR__, 2) . '/pluginora.php';
$wooCommercePluginFile = pluginora_find_woocommerce_plugin_file($wpTestsDir);

if (! $wooCommercePluginFile || ! file_exists($wooCommercePluginFile)) {
    fwrite(
        STDERR,
        "Unable to locate WooCommerce for integration tests. "
        . "Set PLUGINORA_WC_PLUGIN_FILE or install WooCommerce into the local wordpress-develop checkout.\n"
    );
    exit(1);
}

require_once rtrim($wpTestsDir, '/\\') . '/includes/functions.php';

tests_add_filter(
    'muplugins_loaded',
    static function () use ($pluginoraPluginFile, $wooCommercePluginFile): void {
        if (file_exists($wooCommercePluginFile)) {
            require_once $wooCommercePluginFile;
        }

        if (file_exists($pluginoraPluginFile)) {
            require_once $pluginoraPluginFile;
        }
    }
);

require_once rtrim($wpTestsDir, '/\\') . '/includes/bootstrap.php';

function pluginora_find_wp_tests_dir(): ?string
{
    $candidates = [];
    $configured = getenv('WP_TESTS_DIR');

    if (is_string($configured) && '' !== $configured) {
        $candidates[] = $configured;
    }

    $home = getenv('HOME');

    if (is_string($home) && '' !== $home) {
        $candidates[] = $home . '/wordpress-develop/tests/phpunit';
        $candidates[] = $home . '/src/wordpress-develop/tests/phpunit';
    }

    $workspaceRoot = dirname(__DIR__, 2);
    $candidates[] = dirname($workspaceRoot) . '/wordpress-develop/tests/phpunit';

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && is_dir($candidate)) {
            return rtrim($candidate, '/\\');
        }
    }

    return null;
}

function pluginora_find_woocommerce_plugin_file(string $wpTestsDir): ?string
{
    $candidates = [];
    $configured = getenv('PLUGINORA_WC_PLUGIN_FILE');

    if (is_string($configured) && '' !== $configured) {
        $candidates[] = $configured;
    }

    $wpDevelopRoot = dirname(dirname(dirname($wpTestsDir)));
    $candidates[] = $wpDevelopRoot . '/src/wp-content/plugins/woocommerce/woocommerce.php';

    $home = getenv('HOME');

    if (is_string($home) && '' !== $home) {
        $candidates[] = $home . '/wordpress-develop/src/wp-content/plugins/woocommerce/woocommerce.php';
    }

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && file_exists($candidate)) {
            return $candidate;
        }
    }

    return null;
}

// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols
