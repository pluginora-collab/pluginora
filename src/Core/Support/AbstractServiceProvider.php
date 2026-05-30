<?php

declare(strict_types=1);

namespace Pluginora\Core\Support;

use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Contracts\ServiceProviderInterface;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    public function boot(ContainerInterface $container): void
    {
    }
}
