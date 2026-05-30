<?php

declare(strict_types=1);

namespace Pluginora\Frontend\Assets;

use Closure;
use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Core\Support\PluginContext;

final class FrontendAssets implements HookableInterface
{
    public function __construct(
        private readonly PluginContext $context,
        private readonly ?Closure $shouldEnqueueResolver = null
    ) {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        if (! $this->shouldEnqueue()) {
            return;
        }

        wp_enqueue_style(
            'pluginora-frontend',
            $this->context->getPluginUrl() . 'assets/frontend/css/frontend.css',
            [],
            $this->context->getVersion()
        );

        wp_enqueue_script(
            'pluginora-frontend',
            $this->context->getPluginUrl() . 'assets/frontend/js/frontend.js',
            [],
            $this->context->getVersion(),
            true
        );
    }

    private function shouldEnqueue(): bool
    {
        if ($this->shouldEnqueueResolver instanceof Closure) {
            return (bool) ($this->shouldEnqueueResolver)();
        }

        if (! function_exists('is_woocommerce')) {
            return false;
        }

        return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
    }
}
