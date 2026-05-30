<?php

declare(strict_types=1);

namespace Pluginora\Support;

final class RuleItem
{
    public function __construct(
        private readonly string $objectType,
        private readonly int $objectId
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['object_type'],
            (int) $row['object_id']
        );
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function toDatabaseRow(int $ruleId): array
    {
        return [
            'rule_id'     => $ruleId,
            'object_type' => $this->objectType,
            'object_id'   => $this->objectId,
        ];
    }
}
