<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Admin\Pages\RuleBuilderPage;
use Pluginora\Admin\Settings\PluginSettingsPage;

final class RuleBuilderPageIntegrationTest extends IntegrationTestCase
{
    private RuleBuilderPage $page;

    public function set_up(): void
    {
        parent::set_up();

        $this->page = new RuleBuilderPage(new PluginSettingsPage(self::$settingsRepository));
    }

    public function test_register_menu_adds_pluginora_top_level_menu(): void
    {
        global $menu;

        $this->page->registerMenu();

        self::assertIsArray($menu);

        $matches = array_values(
            array_filter(
                $menu,
                static fn (array $item): bool => 'Pluginora' === $item[0] && 'pluginora' === $item[2]
            )
        );

        self::assertCount(1, $matches);
    }

    public function test_render_outputs_rule_builder_shell_for_authorized_user(): void
    {
        ob_start();
        $this->page->render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Pluginora', $output);
        self::assertStringContainsString('id="pluginora-admin-app"', $output);
        self::assertStringContainsString('Loading Pluginora', $output);
        self::assertStringContainsString('Promotion Policy', $output);
        self::assertStringContainsString('Save Settings', $output);
        self::assertStringNotContainsString('Build pricing and coupon campaigns from one branded workspace', $output);
    }
}
