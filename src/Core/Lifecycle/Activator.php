<?php

declare(strict_types=1);

namespace Pluginora\Core\Lifecycle;

use Pluginora\Core\Compatibility\WooCommerceGuard;
use Pluginora\Core\Database\RuleTables;
use Pluginora\Core\Database\SchemaInstaller;
use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Core\Support\PluginContext;

final class Activator
{
    public static function activate(string $pluginFile): void
    {
        $context = PluginContext::fromFile($pluginFile, PLUGINORA_VERSION, PLUGINORA_TEXT_DOMAIN);
        $guard   = new WooCommerceGuard($context);

        if (! $guard->isSatisfied()) {
            deactivate_plugins(plugin_basename($pluginFile));

            wp_die(
                esc_html($guard->getFailureMessage()),
                esc_html__('Plugin Activation Error', 'pluginora'),
                [
                    'back_link' => true,
                ]
            );
        }

        global $wpdb;

        $installer = new SchemaInstaller($wpdb, new RuleTables($wpdb->prefix));
        $installer->installOrUpgrade();

        update_option('pluginora_version', PLUGINORA_VERSION);
        add_option(SettingsRepository::OPTION_KEY, SettingsRepository::defaults());

        flush_rewrite_rules();
    }
}
