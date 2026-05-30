<?php

declare(strict_types=1);

namespace Pluginora\Frontend\Providers;

use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Support\AbstractServiceProvider;
use Pluginora\Frontend\Assets\FrontendAssets;

final class FrontendServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->share(FrontendAssets::class);
    }

    public function boot(ContainerInterface $container): void
    {
        if (is_admin()) {
            return;
        }

        $container->get(FrontendAssets::class)->register();
    }
}
