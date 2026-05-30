<?php

declare(strict_types=1);

namespace Pluginora\Core\Contracts;

interface ContainerInterface
{
    public function bind(string $id, callable|string|null $concrete = null): void;

    public function share(string $id, callable|string|null $concrete = null): void;

    public function instance(string $id, mixed $instance): void;

    public function has(string $id): bool;

    public function get(string $id): mixed;

    public function make(string $id, array $parameters = []): mixed;
}
