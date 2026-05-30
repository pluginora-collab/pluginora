<?php

declare(strict_types=1);

namespace Pluginora\Repository\Contracts;

use Pluginora\Support\Rule;

interface RuleQueryRepositoryInterface
{
    /**
     * @return Rule[]
     */
    public function findActiveByModule(string $module, ?string $currentGmt = null): array;

    /**
     * @return Rule[]
     */
    public function findByFilters(array $filters = []): array;
}
