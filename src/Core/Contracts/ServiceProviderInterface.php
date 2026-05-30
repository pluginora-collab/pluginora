<?php

declare(strict_types=1);

namespace Pluginora\Core\Contracts;

interface ServiceProviderInterface
{
    public function register(ContainerInterface $container): void;

    public function boot(ContainerInterface $container): void;
}
