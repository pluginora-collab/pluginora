<?php

declare(strict_types=1);

namespace Pluginora\Core\Compatibility;

use Pluginora\Core\Support\PluginContext;

final class WooCommerceGuard
{
    public const MINIMUM_PHP_VERSION = '8.1';

    public const MINIMUM_WOOCOMMERCE_VERSION = '8.2';

    public function __construct(private readonly PluginContext $context)
    {
    }

    public function isSatisfied(): bool
    {
        return $this->meetsPhpVersion()
            && $this->isWooCommerceActive()
            && $this->meetsWooCommerceVersion();
    }

    public function registerAdminNotice(): void
    {
        add_action(
            'admin_notices',
            function (): void {
                if (! current_user_can('manage_woocommerce')) {
                    return;
                }

                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html($this->getFailureMessage())
                );
            }
        );
    }

    public function getFailureMessage(): string
    {
        if (! $this->meetsPhpVersion()) {
            return sprintf(
                /* translators: 1: minimum PHP version. */
                __('Pluginora requires PHP %1$s or higher.', 'pluginora'),
                self::MINIMUM_PHP_VERSION
            );
        }

        if (! $this->isWooCommerceActive()) {
            return __('Pluginora requires WooCommerce to be installed and active.', 'pluginora');
        }

        if (! $this->meetsWooCommerceVersion()) {
            return sprintf(
                /* translators: 1: minimum WooCommerce version. */
                __('Pluginora requires WooCommerce %1$s or higher.', 'pluginora'),
                self::MINIMUM_WOOCOMMERCE_VERSION
            );
        }

        return sprintf(
            /* translators: 1: plugin basename. */
            __('Pluginora could not boot correctly. Check the configuration for %1$s.', 'pluginora'),
            $this->context->getPluginBasename()
        );
    }

    public function meetsPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=');
    }

    public function isWooCommerceActive(): bool
    {
        return class_exists('WooCommerce') && defined('WC_VERSION');
    }

    public function meetsWooCommerceVersion(): bool
    {
        if (! defined('WC_VERSION')) {
            return false;
        }

        return version_compare((string) WC_VERSION, self::MINIMUM_WOOCOMMERCE_VERSION, '>=');
    }
}
