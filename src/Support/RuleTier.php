<?php

declare(strict_types=1);

namespace Pluginora\Support;

final class RuleTier
{
    public function __construct(
        private readonly int $minQuantity,
        private readonly ?int $maxQuantity,
        private readonly string $discountType,
        private readonly float $discountValue
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['min_qty'],
            isset($row['max_qty']) ? (int) $row['max_qty'] : null,
            (string) $row['discount_type'],
            (float) $row['discount_value']
        );
    }

    public function getMinQuantity(): int
    {
        return $this->minQuantity;
    }

    public function getMaxQuantity(): ?int
    {
        return $this->maxQuantity;
    }

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function getDiscountValue(): float
    {
        return $this->discountValue;
    }

    public function toDatabaseRow(int $ruleId): array
    {
        return [
            'rule_id'        => $ruleId,
            'min_qty'        => $this->minQuantity,
            'max_qty'        => $this->maxQuantity,
            'discount_type'  => $this->discountType,
            'discount_value' => $this->discountValue,
        ];
    }
}
