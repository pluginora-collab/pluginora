<?php

declare(strict_types=1);

namespace Pluginora\Core\Database;

final class RuleTables
{
    public function __construct(private readonly string $prefix)
    {
    }

    public function all(): array
    {
        return [
            $this->conditions(),
            $this->actions(),
            $this->items(),
            $this->tiers(),
            $this->logs(),
            $this->rules(),
        ];
    }

    public function rules(): string
    {
        return $this->prefix . 'pluginora_rules';
    }

    public function conditions(): string
    {
        return $this->prefix . 'pluginora_rule_conditions';
    }

    public function actions(): string
    {
        return $this->prefix . 'pluginora_rule_actions';
    }

    public function items(): string
    {
        return $this->prefix . 'pluginora_rule_items';
    }

    public function tiers(): string
    {
        return $this->prefix . 'pluginora_rule_tiers';
    }

    public function logs(): string
    {
        return $this->prefix . 'pluginora_rule_logs';
    }

    public function dropStatements(): array
    {
        return array_map(
            static fn (string $table): string => sprintf('DROP TABLE IF EXISTS %s', $table),
            $this->all()
        );
    }
}
