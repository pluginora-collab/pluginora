<?php

declare(strict_types=1);

namespace Pluginora\Admin\Pages;

use Pluginora\Core\Contracts\HookableInterface;

final class RuleBuilderPage implements HookableInterface
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Pluginora', 'pluginora'),
            __('Pluginora', 'pluginora'),
            'manage_woocommerce',
            'pluginora',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You are not allowed to access this page.', 'pluginora'));
        }

        ?>
        <div class="wrap pluginora-admin-page">
            <h1><?php echo esc_html__('Pluginora', 'pluginora'); ?></h1>
            <p>
                <?php
                echo esc_html__(
                    'Start with the rule family, then Pluginora shows only the fields needed for that promotion type.',
                    'pluginora'
                );
                ?>
            </p>
            <div id="pluginora-admin-app" class="pluginora-admin-card" aria-live="polite">
                <p><?php echo esc_html__('Loading Pluginora…', 'pluginora'); ?></p>
            </div>
        </div>
        <?php
    }
}