<?php

declare(strict_types=1);

namespace Pluginora\Core\Database;

final class TableSchema
{
    public function __construct(
        private readonly string $name,
        private readonly string $sql
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSql(): string
    {
        return $this->sql;
    }
}
