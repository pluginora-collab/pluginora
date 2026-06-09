<?php

declare(strict_types=1);

namespace Pluginora\Core\Container;

use InvalidArgumentException;
use Pluginora\Core\Contracts\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container implements ContainerInterface
{
    private array $definitions = [];

    private array $instances = [];

    public function bind(string $id, callable|string|null $concrete = null): void
    {
        $this->set($id, $concrete ?? $id, false);
    }

    public function share(string $id, callable|string|null $concrete = null): void
    {
        $this->set($id, $concrete ?? $id, true);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->instances[$id]) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (! array_key_exists($id, $this->definitions)) {
            return $this->make($id);
        }

        $definition = $this->definitions[$id];
        $resolved   = $this->resolve($definition['concrete']);

        if ($definition['shared']) {
            $this->instances[$id] = $resolved;
        }

        return $resolved;
    }

    public function make(string $id, array $parameters = []): mixed
    {
        if (! class_exists($id)) {
            throw new InvalidArgumentException(sprintf('Unable to resolve service "%s".', $id));
        }

        $reflection = new ReflectionClass($id);

        if (! $reflection->isInstantiable()) {
            throw new RuntimeException(sprintf('Service "%s" is not instantiable.', $id));
        }

        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            return new $id();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();

                if ($this->canResolveType($typeName)) {
                    $dependencies[] = $this->get($typeName);
                    continue;
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            if ($type instanceof ReflectionNamedType && $type->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new RuntimeException(sprintf('Unable to resolve parameter "%s" for "%s".', $name, $id));
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    private function set(string $id, callable|string $concrete, bool $shared): void
    {
        $this->definitions[$id] = [
            'concrete' => $concrete,
            'shared'   => $shared,
        ];

        unset($this->instances[$id]);
    }

    private function resolve(callable|string $concrete): mixed
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        return $this->make($concrete);
    }

    private function canResolveType(string $id): bool
    {
        if (array_key_exists($id, $this->instances) || array_key_exists($id, $this->definitions)) {
            return true;
        }

        if (! class_exists($id)) {
            return false;
        }

        $reflection = new ReflectionClass($id);

        return $reflection->isInstantiable();
    }
}
