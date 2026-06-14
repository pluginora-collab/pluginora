<?php

/**
 * Plugin Name: Pluginora
 * Plugin URI: https://example.com/pluginora
 * Description: Modular WooCommerce dynamic pricing and coupon orchestration.
 * Version: 1.0.6
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Pluginora
 * Text Domain: pluginora
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * @package Pluginora
 */

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

if (! defined('ABSPATH')) {
    exit;
}

define('PLUGINORA_VERSION', '1.0.6');
define('PLUGINORA_FILE', __FILE__);
define('PLUGINORA_PATH', plugin_dir_path(__FILE__));
define('PLUGINORA_URL', plugin_dir_url(__FILE__));
define('PLUGINORA_BASENAME', plugin_basename(__FILE__));
define('PLUGINORA_TEXT_DOMAIN', 'pluginora');

add_filter(
    'plugin_action_links_' . PLUGINORA_BASENAME,
    static function (array $links): array {
        if (! current_user_can('manage_woocommerce')) {
            return $links;
        }

        array_unshift(
            $links,
            sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=pluginora')),
                esc_html__('Settings', 'pluginora')
            )
        );

        return $links;
    }
);

register_activation_hook(
    PLUGINORA_FILE,
    static function (): void {
        $autoload_file = PLUGINORA_PATH . 'vendor/autoload.php';

        if (! file_exists($autoload_file)) {
            deactivate_plugins(PLUGINORA_BASENAME);

            wp_die(
                esc_html__(
                    'Pluginora is missing Composer dependencies. Run "composer install" before activation.',
                    'pluginora'
                ),
                esc_html__('Plugin Activation Error', 'pluginora'),
                [
                    'back_link' => true,
                ]
            );
        }

        require_once $autoload_file;

        Pluginora\Core\Lifecycle\Activator::activate(PLUGINORA_FILE);
    }
);

register_deactivation_hook(
    PLUGINORA_FILE,
    static function (): void {
        $autoload_file = PLUGINORA_PATH . 'vendor/autoload.php';

        if (! file_exists($autoload_file)) {
            return;
        }

        require_once $autoload_file;

        Pluginora\Core\Lifecycle\Deactivator::deactivate();
    }
);

$pluginora_autoload = PLUGINORA_PATH . 'vendor/autoload.php';

if (! file_exists($pluginora_autoload)) {
    add_action(
        'admin_notices',
        static function (): void {
            if (! current_user_can('activate_plugins')) {
                return;
            }

            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: 1: command to install dependencies. */
                    esc_html__(
                        'Pluginora is missing Composer dependencies. Run "%s" before activating it.',
                        'pluginora'
                    ),
                    'composer install'
                )
            );
        }
    );

    return;
}

require_once $pluginora_autoload;

add_action(
    'before_woocommerce_init',
    static function (): void {
        (new Pluginora\Core\Compatibility\HposCompatibility(PLUGINORA_FILE))
            ->declare();
    }
);

add_action(
    'plugins_loaded',
    static function (): void {
        $plugin = Pluginora\Core\Bootstrap\PluginFactory::create(
            PLUGINORA_FILE,
            PLUGINORA_VERSION,
            PLUGINORA_TEXT_DOMAIN
        );

        $plugin->boot();
    },
    20
);

// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols
