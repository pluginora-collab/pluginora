<?php

declare(strict_types=1);

namespace Pluginora\Support;

final class RuleCondition
{
    public function __construct(
        private readonly string $conditionType,
        private readonly string $operator,
        private readonly mixed $conditionValue,
        private readonly int $sortOrder = 0
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['condition_type'],
            (string) $row['operator'],
            json_decode((string) $row['condition_value'], true),
            isset($row['sort_order']) ? (int) $row['sort_order'] : 0
        );
    }

    public function getConditionType(): string
    {
        return $this->conditionType;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getConditionValue(): mixed
    {
        return $this->conditionValue;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function toDatabaseRow(int $ruleId): array
    {
        return [
            'rule_id'         => $ruleId,
            'condition_type'  => $this->conditionType,
            'operator'        => $this->operator,
            'condition_value' => wp_json_encode($this->conditionValue),
            'sort_order'      => $this->sortOrder,
        ];
    }
}
