<?php

declare(strict_types=1);

namespace Pluginora\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pluginora\Support\Rule;
use Pluginora\Support\RuleAction;
use Pluginora\Support\RuleCondition;
use Pluginora\Support\RuleItem;
use Pluginora\Support\RuleTier;

final class RuleTest extends TestCase
{
    public function test_duplicate_resets_id_and_status(): void
    {
        $rule = new Rule(
            12,
            'dynamic_pricing',
            'simple_discount',
            'Spring Sale',
            'active',
            3,
            null,
            '2026-05-01 00:00:00',
            '2026-05-31 23:59:59',
            [new RuleCondition('applies_to', '=', 'all_products')],
            [new RuleAction('discount_type', 'percentage')],
            [new RuleItem('product', 42)],
            [new RuleTier(1, 5, 'percentage', 10.0)]
        );

        $duplicate = $rule->duplicate();

        self::assertNull($duplicate->getId());
        self::assertSame('inactive', $duplicate->getStatus());
        self::assertSame('Spring Sale (Copy)', $duplicate->getName());
        self::assertCount(1, $duplicate->getConditions());
        self::assertCount(1, $duplicate->getActions());
        self::assertCount(1, $duplicate->getItems());
        self::assertCount(1, $duplicate->getTiers());
    }

    public function test_with_status_returns_new_instance_with_updated_status(): void
    {
        $rule = new Rule(
            15,
            'coupon_engine',
            'basic_coupon',
            'Welcome Coupon',
            'inactive',
            10,
            null,
            null,
            null
        );

        $updated = $rule->withStatus('active');

        self::assertSame('inactive', $rule->getStatus());
        self::assertSame('active', $updated->getStatus());
        self::assertSame($rule->getId(), $updated->getId());
    }
}
