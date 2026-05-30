<?php

declare(strict_types=1);

namespace Pluginora\Core\Contracts;

interface ModuleInterface
{
    public function getSlug(): string;

    public function register(ContainerInterface $container): void;

    public function boot(ContainerInterface $container): void;
}
