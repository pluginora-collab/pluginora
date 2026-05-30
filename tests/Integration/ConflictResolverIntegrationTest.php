<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Core\Settings\SettingsRepository;
use WC_Cart;
use WC_Product;

final class ConflictResolverIntegrationTest extends IntegrationTestCase
{
    private WC_Cart $cart;

    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Cart') || ! class_exists('WC_Product')) {
            self::markTestSkipped('WooCommerce is required for conflict resolver integration tests.');
        }

        $this->cart = $this->createCartDouble(101, 100.0, 1, 100.0);
    }

    public function test_best_discount_only_prefers_coupon_when_coupon_saves_more(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'best_discount_only']);

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'selected_products' => [101],
                        'discount_value'    => 20,
                    ]
                )
            )
        );

        $couponRule = self::$rulePayloadMapper->fromPayload(
            $this->makeAutoApplyCouponPayload(
                [
                    'coupon_amount' => 30,
                ]
            )
        );
        $couponRuleId = self::$ruleRepository->save($couponRule);
        $storedCouponRule = self::$ruleRepository->find($couponRuleId);

        self::assertFalse(self::$conflictResolver->shouldApplyDynamicPricing($this->cart));
        self::assertTrue(self::$conflictResolver->shouldApplyCouponRule($this->cart, $storedCouponRule));
    }

    public function test_stack_all_allows_both_promotion_paths(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'selected_products' => [101],
                        'discount_value'    => 10,
                    ]
                )
            )
        );

        $couponRuleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload($this->makeAutoApplyCouponPayload())
        );
        $storedCouponRule = self::$ruleRepository->find($couponRuleId);

        self::assertTrue(self::$conflictResolver->shouldApplyDynamicPricing($this->cart));
        self::assertTrue(self::$conflictResolver->shouldApplyCouponRule($this->cart, $storedCouponRule));
    }

    public function test_coupon_priority_blocks_dynamic_pricing_when_coupon_value_exists(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'coupon_priority']);

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'selected_products' => [101],
                        'discount_value'    => 40,
                    ]
                )
            )
        );

        $couponRuleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload($this->makeAutoApplyCouponPayload())
        );
        $storedCouponRule = self::$ruleRepository->find($couponRuleId);

        self::assertFalse(self::$conflictResolver->shouldApplyDynamicPricing($this->cart));
        self::assertTrue(self::$conflictResolver->shouldApplyCouponRule($this->cart, $storedCouponRule));
    }

    private function createCartDouble(int $productId, float $price, int $quantity, float $subtotal): WC_Cart
    {
        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn($productId);
        $product->method('get_parent_id')->willReturn(0);
        $product->method('get_price')->willReturnCallback(static fn (): string => (string) $price);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_cart', 'get_subtotal', 'get_applied_coupons'])
            ->getMock();

        $cart->method('get_cart')->willReturn(
            [
                [
                    'data'     => $product,
                    'quantity' => $quantity,
                ],
            ]
        );
        $cart->method('get_subtotal')->willReturn($subtotal);
        $cart->method('get_applied_coupons')->willReturn([]);

        return $cart;
    }
}
