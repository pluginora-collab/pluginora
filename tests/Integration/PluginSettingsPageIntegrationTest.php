<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Admin\Settings\PluginSettingsPage;
use Pluginora\Core\Settings\SettingsRepository;

final class PluginSettingsPageIntegrationTest extends IntegrationTestCase
{
    private PluginSettingsPage $settingsPage;

    public function set_up(): void
    {
        parent::set_up();

        $this->settingsPage = new PluginSettingsPage(self::$settingsRepository);
    }

    public function test_register_settings_registers_plugin_option(): void
    {
        global $wp_registered_settings;

        $this->settingsPage->registerSettings();

        self::assertIsArray($wp_registered_settings);
        self::assertArrayHasKey(SettingsRepository::OPTION_KEY, $wp_registered_settings);
        self::assertSame('array', $wp_registered_settings[SettingsRepository::OPTION_KEY]['type']);
        self::assertSame(
            SettingsRepository::defaults(),
            $wp_registered_settings[SettingsRepository::OPTION_KEY]['default']
        );
    }

    public function test_sanitize_settings_accepts_valid_mode_and_normalizes_invalid_mode(): void
    {
        self::assertSame(
            ['conflict_mode' => 'stack_all'],
            $this->settingsPage->sanitizeSettings(['conflict_mode' => 'stack_all'])
        );

        self::assertSame(
            ['conflict_mode' => 'best_discount_only'],
            $this->settingsPage->sanitizeSettings(['conflict_mode' => 'totally_invalid'])
        );
    }

    public function test_render_conflict_mode_field_reflects_saved_option(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'coupon_priority']);

        ob_start();
        $this->settingsPage->renderConflictModeField();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('name="pluginora_settings[conflict_mode]"', $output);
        self::assertStringContainsString('value="coupon_priority"', $output);
        self::assertStringContainsString("selected='selected'", $output);
        self::assertStringContainsString('Best Discount Only is the default', $output);
    }

    public function test_render_outputs_settings_form_for_authorized_user(): void
    {
        $this->settingsPage->registerSettings();

        ob_start();
        $this->settingsPage->render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Pluginora Settings', $output);
        self::assertStringContainsString('method="post"', $output);
        self::assertStringContainsString('action="options.php"', $output);
        self::assertStringContainsString('pluginora_settings_group', $output);
        self::assertStringContainsString('Save Settings', $output);
    }
}
