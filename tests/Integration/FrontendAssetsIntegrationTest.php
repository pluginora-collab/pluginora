<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Core\Support\PluginContext;
use Pluginora\Frontend\Assets\FrontendAssets;

final class FrontendAssetsIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        wp_dequeue_style('pluginora-frontend');
        wp_deregister_style('pluginora-frontend');
        wp_dequeue_script('pluginora-frontend');
        wp_deregister_script('pluginora-frontend');
    }

    public function test_enqueue_skips_non_woocommerce_contexts(): void
    {
        $assets = $this->makeAssets(static fn (): bool => false);

        $assets->enqueue();

        self::assertFalse(wp_style_is('pluginora-frontend', 'enqueued'));
        self::assertFalse(wp_script_is('pluginora-frontend', 'enqueued'));
    }

    public function test_enqueue_registers_frontend_assets_in_supported_contexts(): void
    {
        $assets = $this->makeAssets(static fn (): bool => true);

        $assets->enqueue();

        self::assertTrue(wp_style_is('pluginora-frontend', 'enqueued'));
        self::assertTrue(wp_script_is('pluginora-frontend', 'enqueued'));

        $style = wp_styles()->registered['pluginora-frontend'] ?? null;
        $script = wp_scripts()->registered['pluginora-frontend'] ?? null;

        self::assertNotNull($style);
        self::assertNotNull($script);
        self::assertStringContainsString('assets/frontend/css/frontend.css', (string) $style->src);
        self::assertStringContainsString('assets/frontend/js/frontend.js', (string) $script->src);
        self::assertSame('1.0.0', (string) $style->ver);
        self::assertSame('1.0.0', (string) $script->ver);
    }

    private function makeAssets(\Closure $shouldEnqueueResolver): FrontendAssets
    {
        return new FrontendAssets(
            new PluginContext(
                '/Users/abhishektiwari/pluginora/pluginora.php',
                'pluginora/pluginora.php',
                '/Users/abhishektiwari/pluginora/',
                'https://example.org/wp-content/plugins/pluginora/',
                '1.0.0',
                'pluginora'
            ),
            $shouldEnqueueResolver
        );
    }
}
