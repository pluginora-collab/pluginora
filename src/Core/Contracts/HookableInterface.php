<?php

declare(strict_types=1);

namespace Pluginora\Core\Contracts;

interface HookableInterface
{
    public function register(): void;
}
