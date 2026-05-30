<?php

declare(strict_types=1);

namespace Pluginora\Support;

final class RuleAction
{
    public function __construct(
        private readonly string $actionType,
        private readonly mixed $actionValue,
        private readonly int $sortOrder = 0
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['action_type'],
            json_decode((string) $row['action_value'], true),
            isset($row['sort_order']) ? (int) $row['sort_order'] : 0
        );
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getActionValue(): mixed
    {
        return $this->actionValue;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function toDatabaseRow(int $ruleId): array
    {
        return [
            'rule_id'      => $ruleId,
            'action_type'  => $this->actionType,
            'action_value' => wp_json_encode($this->actionValue),
            'sort_order'   => $this->sortOrder,
        ];
    }
}
