<?php

declare(strict_types=1);

namespace Pluginora\Support;

final class Rule
{
    /**
     * @param RuleCondition[] $conditions
     * @param RuleAction[]    $actions
     * @param RuleItem[]      $items
     * @param RuleTier[]      $tiers
     */
    public function __construct(
        private readonly ?int $id,
        private readonly string $module,
        private readonly string $ruleType,
        private readonly string $name,
        private readonly string $status,
        private readonly int $priority,
        private readonly ?string $stackModeOverride,
        private readonly ?string $startsAtGmt,
        private readonly ?string $endsAtGmt,
        private readonly array $conditions = [],
        private readonly array $actions = [],
        private readonly array $items = [],
        private readonly array $tiers = []
    ) {
    }

    /**
     * @param RuleCondition[] $conditions
     * @param RuleAction[]    $actions
     * @param RuleItem[]      $items
     * @param RuleTier[]      $tiers
     */
    public static function fromRow(
        array $row,
        array $conditions = [],
        array $actions = [],
        array $items = [],
        array $tiers = []
    ): self {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) $row['module'],
            (string) $row['rule_type'],
            (string) $row['name'],
            isset($row['status']) ? (string) $row['status'] : 'inactive',
            isset($row['priority']) ? (int) $row['priority'] : 1,
            isset($row['stack_mode_override']) ? (string) $row['stack_mode_override'] : null,
            isset($row['starts_at_gmt']) ? (string) $row['starts_at_gmt'] : null,
            isset($row['ends_at_gmt']) ? (string) $row['ends_at_gmt'] : null,
            $conditions,
            $actions,
            $items,
            $tiers
        );
    }

    public function duplicate(string $suffix = ' (Copy)'): self
    {
        return new self(
            null,
            $this->module,
            $this->ruleType,
            $this->name . $suffix,
            'inactive',
            $this->priority,
            $this->stackModeOverride,
            $this->startsAtGmt,
            $this->endsAtGmt,
            $this->conditions,
            $this->actions,
            $this->items,
            $this->tiers
        );
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->module,
            $this->ruleType,
            $this->name,
            $this->status,
            $this->priority,
            $this->stackModeOverride,
            $this->startsAtGmt,
            $this->endsAtGmt,
            $this->conditions,
            $this->actions,
            $this->items,
            $this->tiers
        );
    }

    public function withStatus(string $status): self
    {
        return new self(
            $this->id,
            $this->module,
            $this->ruleType,
            $this->name,
            $status,
            $this->priority,
            $this->stackModeOverride,
            $this->startsAtGmt,
            $this->endsAtGmt,
            $this->conditions,
            $this->actions,
            $this->items,
            $this->tiers
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getRuleType(): string
    {
        return $this->ruleType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getStackModeOverride(): ?string
    {
        return $this->stackModeOverride;
    }

    public function getStartsAtGmt(): ?string
    {
        return $this->startsAtGmt;
    }

    public function getEndsAtGmt(): ?string
    {
        return $this->endsAtGmt;
    }

    /**
     * @return RuleCondition[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return RuleAction[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return RuleItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return RuleTier[]
     */
    public function getTiers(): array
    {
        return $this->tiers;
    }

    public function toDatabaseRow(): array
    {
        $now = gmdate('Y-m-d H:i:s');

        return [
            'module'              => $this->module,
            'rule_type'           => $this->ruleType,
            'name'                => $this->name,
            'status'              => $this->status,
            'priority'            => $this->priority,
            'stack_mode_override' => $this->stackModeOverride,
            'starts_at_gmt'       => $this->startsAtGmt,
            'ends_at_gmt'         => $this->endsAtGmt,
            'updated_at_gmt'      => $now,
            'created_at_gmt'      => $now,
        ];
    }
}
