<?php

declare(strict_types=1);

namespace Pluginora\Core\Bootstrap;

use Pluginora\Core\Compatibility\WooCommerceGuard;
use Pluginora\Core\Database\SchemaInstaller;
use Pluginora\Core\Support\PluginContext;

final class Plugin
{
    public function __construct(
        private readonly PluginContext $context,
        private readonly Loader $loader,
        private readonly WooCommerceGuard $wooCommerceGuard,
        private readonly SchemaInstaller $schemaInstaller
    ) {
    }

    public function boot(): void
    {
        if (! $this->wooCommerceGuard->isSatisfied()) {
            $this->wooCommerceGuard->registerAdminNotice();
            return;
        }

        $this->schemaInstaller->installOrUpgrade();
        $this->syncPluginVersion();

        add_action('init', [$this, 'loadTextdomain']);

        $this->loader->boot();
    }

    private function syncPluginVersion(): void
    {
        $storedVersion = get_option('pluginora_version', '');

        if ($storedVersion !== $this->context->getVersion()) {
            update_option('pluginora_version', $this->context->getVersion());
        }
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
