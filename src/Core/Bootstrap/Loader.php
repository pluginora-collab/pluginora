<?php

declare(strict_types=1);

namespace Pluginora\Core\Bootstrap;

use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Contracts\ModuleInterface;
use Pluginora\Core\Contracts\ServiceProviderInterface;

final class Loader
{
    private array $providers = [];

    private array $modules = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function addProvider(ServiceProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function addModule(ModuleInterface $module): void
    {
        $this->modules[] = $module;
    }

    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->register($this->container);
        }

        foreach ($this->modules as $module) {
            $module->register($this->container);
        }

        foreach ($this->providers as $provider) {
            $provider->boot($this->container);
        }

        foreach ($this->modules as $module) {
            $module->boot($this->container);
        }
    }
}
