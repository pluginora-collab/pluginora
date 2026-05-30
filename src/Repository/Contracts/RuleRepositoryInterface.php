<?php

declare(strict_types=1);

namespace Pluginora\Repository\Contracts;

use Pluginora\Support\Rule;

interface RuleRepositoryInterface
{
    public function find(int $ruleId): ?Rule;

    public function save(Rule $rule): int;

    public function delete(int $ruleId): bool;

    public function duplicate(int $ruleId): int;

    public function updateStatus(int $ruleId, string $status): bool;
}
