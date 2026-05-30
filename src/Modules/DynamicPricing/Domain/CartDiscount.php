<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Domain;

use Pluginora\Support\Rule;

final class CartDiscount
{
    public function __construct(
        private readonly Rule $rule,
        private readonly float $cartSubtotal,
        private readonly float $discountAmount,
        private readonly float $minimumAmount,
        private readonly ?float $maximumAmount,
        private readonly string $discountType,
        private readonly float $discountValue,
        private readonly bool $savingsMessageEnabled
    ) {
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }

    public function getCartSubtotal(): float
    {
        return $this->cartSubtotal;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function getMinimumAmount(): float
    {
        return $this->minimumAmount;
    }

    public function getMaximumAmount(): ?float
    {
        return $this->maximumAmount;
    }

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function getDiscountValue(): float
    {
        return $this->discountValue;
    }

    public function isSavingsMessageEnabled(): bool
    {
        return $this->savingsMessageEnabled;
    }
}
