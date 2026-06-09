<?php

declare(strict_types=1);

namespace Pluginora\Admin\Pages;

use Pluginora\Admin\Settings\PluginSettingsPage;
use Pluginora\Core\Contracts\HookableInterface;

final class RuleBuilderPage implements HookableInterface
{
    public function __construct(private readonly ?PluginSettingsPage $settingsPage = null)
    {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('Pluginora', 'pluginora'),
            __('Pluginora', 'pluginora'),
            'manage_woocommerce',
            'pluginora',
            [$this, 'render'],
            'dashicons-megaphone',
            56
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You are not allowed to access this page.', 'pluginora'));
        }

        ?>
        <div class="wrap pluginora-admin-page">
            <section class="pluginora-page-hero">
                <div class="pluginora-page-hero__content">
                    <span class="pluginora-page-eyebrow"><?php echo esc_html__('Promotion Operations', 'pluginora'); ?></span>
                    <h1><?php echo esc_html__('Pluginora', 'pluginora'); ?></h1>
                    <p>
                        <?php
                        echo esc_html__(
                            'Build pricing and coupon campaigns from one branded workspace with clearer rule creation, management, and promotion policy controls.',
                            'pluginora'
                        );
                        ?>
                    </p>
                </div>
                <div class="pluginora-page-hero__meta">
                    <span class="pluginora-page-pill"><?php echo esc_html__('Dynamic Pricing', 'pluginora'); ?></span>
                    <span class="pluginora-page-pill"><?php echo esc_html__('Coupons', 'pluginora'); ?></span>
                    <span class="pluginora-page-pill"><?php echo esc_html__('BOGO', 'pluginora'); ?></span>
                </div>
            </section>
            <div id="pluginora-admin-app" class="pluginora-admin-card" aria-live="polite">
                <p><?php echo esc_html__('Loading Pluginora…', 'pluginora'); ?></p>
            </div>
            <?php if (null !== $this->settingsPage) : ?>
                <section class="pluginora-admin-card pluginora-settings-card">
                    <h2><?php echo esc_html__('Promotion Policy', 'pluginora'); ?></h2>
                    <p>
                        <?php
                        echo esc_html__(
                            'Configure how Pluginora resolves conflicts between dynamic pricing and coupon rules.',
                            'pluginora'
                        );
                        ?>
                    </p>
                    <?php $this->settingsPage->renderEmbedded(); ?>
                </section>
            <?php endif; ?>
        </div>
        <?php
    }
}