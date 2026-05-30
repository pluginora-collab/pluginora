<?php

declare(strict_types=1);

namespace Pluginora\Core\Support;

use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Contracts\ModuleInterface;

abstract class AbstractModule implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
    }

    public function boot(ContainerInterface $container): void
    {
    }
}
