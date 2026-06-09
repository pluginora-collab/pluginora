<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Admin\Assets\AdminAssets;
use Pluginora\Core\Support\PluginContext;

final class AdminAssetsIntegrationTest extends IntegrationTestCase
{
    private AdminAssets $assets;

    public function set_up(): void
    {
        parent::set_up();

        $context = new PluginContext(
            '/Users/abhishektiwari/pluginora/pluginora.php',
            'pluginora/pluginora.php',
            '/Users/abhishektiwari/pluginora/',
            'https://example.org/wp-content/plugins/pluginora/',
            '1.0.5',
            'pluginora'
        );

        $this->assets = new AdminAssets($context);

        wp_dequeue_style('pluginora-admin');
        wp_deregister_style('pluginora-admin');
        wp_dequeue_script('pluginora-admin');
        wp_deregister_script('pluginora-admin');
    }

    public function test_enqueue_skips_unrelated_admin_pages(): void
    {
        $this->assets->enqueue('woocommerce_page_something_else');

        self::assertFalse(wp_style_is('pluginora-admin', 'enqueued'));
        self::assertFalse(wp_script_is('pluginora-admin', 'enqueued'));
    }

    public function test_enqueue_registers_assets_and_inline_bootstrap_data_for_pluginora_pages(): void
    {
        $this->assets->enqueue('woocommerce_page_pluginora');

        self::assertTrue(wp_style_is('pluginora-admin', 'enqueued'));
        self::assertTrue(wp_script_is('pluginora-admin', 'enqueued'));

        $style = wp_styles()->registered['pluginora-admin'] ?? null;
        $script = wp_scripts()->registered['pluginora-admin'] ?? null;
        $inlineBefore = implode(
            "\n",
            array_filter(
                $script?->extra['before'] ?? [],
                static fn (mixed $value): bool => is_string($value) && '' !== $value
            )
        );

        self::assertNotNull($style);
        self::assertNotNull($script);
        self::assertStringContainsString('assets/admin/css/admin.css', (string) $style->src);
        self::assertStringContainsString('assets/admin/js/admin.js', (string) $script->src);
        self::assertSame('1.0.5', (string) $style->ver);
        self::assertSame('1.0.5', (string) $script->ver);
        self::assertStringContainsString('window.pluginoraAdmin = ', $inlineBefore);
        self::assertStringContainsString('pluginora\\/v1\\/', $inlineBefore);
        self::assertStringContainsString('Promotion rule saved successfully.', $inlineBefore);
        self::assertStringContainsString('Promotion Library', $inlineBefore);
        self::assertStringContainsString('Search products or categories', $inlineBefore);
    }
}
