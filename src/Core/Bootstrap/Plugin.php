<?php

declare(strict_types=1);

namespace Pluginora\Core\Bootstrap;

use Pluginora\Core\Compatibility\WooCommerceGuard;
use Pluginora\Core\Support\PluginContext;

final class Plugin
{
    public function __construct(
        private readonly PluginContext $context,
        private readonly Loader $loader,
        private readonly WooCommerceGuard $wooCommerceGuard
    ) {
    }

    public function boot(): void
    {
        if (! $this->wooCommerceGuard->isSatisfied()) {
            $this->wooCommerceGuard->registerAdminNotice();
            return;
        }

        add_action('init', [$this, 'loadTextdomain']);

        $this->loader->boot();
    }

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            $this->context->getTextDomain(),
            false,
            $this->context->getLanguagesRelativePath()
        );
    }
}
