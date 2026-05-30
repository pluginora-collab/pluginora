<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Domain;

use Pluginora\Support\Rule;

final class ProductDiscount
{
    public function __construct(
        private readonly Rule $rule,
        private readonly float $originalPrice,
        private readonly float $discountedPrice,
        private readonly string $discountType,
        private readonly float $discountValue,
        private readonly bool $badgeEnabled = false,
        private readonly string $badgeText = '',
        private readonly bool $savingsMessageEnabled = false
    ) {
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }

    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    public function getDiscountedPrice(): float
    {
        return $this->discountedPrice;
    }

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function getDiscountValue(): float
    {
        return $this->discountValue;
    }

    public function isBadgeEnabled(): bool
    {
        return $this->badgeEnabled;
    }

    public function getBadgeText(): string
    {
        return $this->badgeText;
    }

    public function isSavingsMessageEnabled(): bool
    {
        return $this->savingsMessageEnabled;
    }

    public function getSavingsAmount(): float
    {
        return max(0.0, $this->originalPrice - $this->discountedPrice);
    }

    public function getSavingsPercent(): float
    {
        if ($this->originalPrice <= 0) {
            return 0.0;
        }

        return round(($this->getSavingsAmount() / $this->originalPrice) * 100, 2);
    }
}
