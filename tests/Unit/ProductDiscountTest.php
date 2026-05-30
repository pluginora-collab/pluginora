<?php

declare(strict_types=1);

namespace Pluginora\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pluginora\Modules\DynamicPricing\Domain\ProductDiscount;
use Pluginora\Support\Rule;

final class ProductDiscountTest extends TestCase
{
    public function test_savings_amount_and_percent_are_calculated(): void
    {
        $rule = new Rule(
            9,
            'dynamic_pricing',
            'simple_discount',
            'Flash Sale',
            'active',
            1,
            null,
            null,
            null
        );

        $discount = new ProductDiscount(
            $rule,
            100.0,
            80.0,
            'percentage',
            20.0,
            true,
            '-20%',
            true
        );

        self::assertSame(20.0, $discount->getSavingsAmount());
        self::assertSame(20.0, $discount->getSavingsPercent());
        self::assertTrue($discount->isBadgeEnabled());
        self::assertTrue($discount->isSavingsMessageEnabled());
    }
}
