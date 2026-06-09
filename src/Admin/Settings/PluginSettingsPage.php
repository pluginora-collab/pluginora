<?php

declare(strict_types=1);

namespace Pluginora\Admin\Settings;

use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Core\Settings\SettingsRepository;

final class PluginSettingsPage implements HookableInterface
{
    public function __construct(private readonly SettingsRepository $settingsRepository)
    {
    }

    public function register(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void
    {
        register_setting(
            'pluginora_settings_group',
            SettingsRepository::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default'           => $this->settingsRepository->all(),
            ]
        );

        add_settings_section(
            'pluginora_general_section',
            __('Promotion Policy', 'pluginora'),
            function (): void {
                echo '<p>' . esc_html__(
                    'Choose how Pluginora resolves conflicts between dynamic pricing and coupon promotions.',
                    'pluginora'
                ) . '</p>';
            },
            'pluginora-settings'
        );

        add_settings_field(
            'pluginora_conflict_mode',
            __('Conflict Mode', 'pluginora'),
            [$this, 'renderConflictModeField'],
            'pluginora-settings',
            'pluginora_general_section'
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function sanitizeSettings(array $settings): array
    {
        return $this->settingsRepository->sanitize($settings);
    }

    public function renderConflictModeField(): void
    {
        $settings = $this->settingsRepository->all();
        $value    = (string) ($settings['conflict_mode'] ?? 'best_discount_only');

        echo '<select name="' . esc_attr(SettingsRepository::OPTION_KEY) . '[conflict_mode]">';

        foreach ($this->settingsRepository->getConflictModeOptions() as $optionValue => $label) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($optionValue),
                selected($value, $optionValue, false),
                esc_html($label)
            );
        }

        echo '</select>';
        echo '<p class="description">' . esc_html__(
            'Best Discount Only is the default and prevents promotion stacking.',
            'pluginora'
        ) . '</p>';
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(
                esc_html__('You are not allowed to access this page.', 'pluginora')
            );
        }

        ?>
        <div class="wrap pluginora-admin-page">
            <h1><?php echo esc_html__('Pluginora Settings', 'pluginora'); ?></h1>
            <?php $this->renderEmbedded(); ?>
        </div>
        <?php
    }

    public function renderEmbedded(): void
    {
        settings_errors();
        ?>
        <form
            method="post"
            action="options.php"
            class="pluginora-admin-card"
        >
            <?php
            settings_fields('pluginora_settings_group');
            do_settings_sections('pluginora-settings');
            submit_button(__('Save Settings', 'pluginora'));
            ?>
        </form>
        <?php
    }
}