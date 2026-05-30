<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Admin\Pages\RuleBuilderPage;

final class RuleBuilderPageIntegrationTest extends IntegrationTestCase
{
    private RuleBuilderPage $page;

    public function set_up(): void
    {
        parent::set_up();

        $this->page = new RuleBuilderPage();
    }

    public function test_register_menu_adds_pluginora_submenu_under_woocommerce(): void
    {
        global $submenu;

        $this->page->registerMenu();

        self::assertIsArray($submenu);
        self::assertArrayHasKey('woocommerce', $submenu);

        $matches = array_values(
            array_filter(
                $submenu['woocommerce'],
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
        self::assertStringContainsString('Start with the rule family', $output);
        self::assertStringContainsString('id="pluginora-admin-app"', $output);
        self::assertStringContainsString('Loading Pluginora', $output);
    }
}
