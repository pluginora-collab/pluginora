<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;
use Pluginora\Modules\CouponEngine\Presentation\AutoApplyCoupons;
use WC_Cart;
use WC_Product;

final class AutoApplyCouponsIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Cart') || ! class_exists('WC_Product') || ! function_exists('wc_add_notice')) {
            self::markTestSkipped('WooCommerce cart APIs are required for auto-apply coupon integration tests.');
        }
    }

    public function test_maybe_apply_coupons_to_cart_applies_matching_coupon(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeAutoApplyCouponPayload()));

        $handler = new AutoApplyCoupons(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(101);
        $product->method('get_parent_id')->willReturn(0);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_subtotal', 'get_cart', 'has_discount', 'apply_coupon', 'remove_coupon'])
            ->getMock();

        $cart->method('get_subtotal')->willReturn(100.0);
        $cart->method('get_cart')->willReturn(
            [
                [
                    'data'     => $product,
                    'quantity' => 1,
                ],
            ]
        );
        $cart->expects(self::once())
            ->method('has_discount')
            ->with('AUTO30')
            ->willReturn(false);
        $cart->expects(self::once())
            ->method('apply_coupon')
            ->with('AUTO30')
            ->willReturn(true);
        $cart->expects(self::never())->method('remove_coupon');

        $handler->maybeApplyCouponsToCart($cart);
    }

    public function test_maybe_apply_coupons_to_cart_removes_coupon_when_rule_no_longer_matches(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeAutoApplyCouponPayload()));

        $handler = new AutoApplyCoupons(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(101);
        $product->method('get_parent_id')->willReturn(0);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_subtotal', 'get_cart', 'has_discount', 'apply_coupon', 'remove_coupon'])
            ->getMock();

        $cart->method('get_subtotal')->willReturn(10.0);
        $cart->method('get_cart')->willReturn(
            [
                [
                    'data'     => $product,
                    'quantity' => 1,
                ],
            ]
        );
        $cart->expects(self::once())
            ->method('has_discount')
            ->with('AUTO30')
            ->willReturn(true);
        $cart->expects(self::never())->method('apply_coupon');
        $cart->expects(self::once())
            ->method('remove_coupon')
            ->with('AUTO30');

        $handler->maybeApplyCouponsToCart($cart);
    }

    public function test_maybe_apply_coupons_to_cart_skips_coupon_when_dynamic_pricing_saves_more(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'best_discount_only']);

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'applies_to'        => 'selected_products',
                        'selected_products' => [101],
                        'discount_value'    => 40,
                    ]
                )
            )
        );
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeAutoApplyCouponPayload(
                    [
                        'coupon_amount' => 10,
                    ]
                )
            )
        );

        $handler = new AutoApplyCoupons(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(101);
        $product->method('get_parent_id')->willReturn(0);
        $product->method('get_price')->willReturnCallback(static fn (): string => '100');

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'get_subtotal',
                'get_cart',
                'get_applied_coupons',
                'has_discount',
                'apply_coupon',
                'remove_coupon',
            ])
            ->getMock();

        $cart->method('get_subtotal')->willReturn(100.0);
        $cart->method('get_applied_coupons')->willReturn([]);
        $cart->method('get_cart')->willReturn(
            [
                [
                    'data'     => $product,
                    'quantity' => 1,
                ],
            ]
        );
        $cart->expects(self::once())
            ->method('has_discount')
            ->with('AUTO30')
            ->willReturn(false);
        $cart->expects(self::never())->method('apply_coupon');
        $cart->expects(self::never())->method('remove_coupon');

        $handler->maybeApplyCouponsToCart($cart);
    }

    public function test_maybe_apply_coupons_to_cart_removes_applied_coupon_when_dynamic_pricing_becomes_better(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'best_discount_only']);

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'applies_to'        => 'selected_products',
                        'selected_products' => [101],
                        'discount_value'    => 35,
                    ]
                )
            )
        );
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeAutoApplyCouponPayload(
                    [
                        'coupon_amount' => 10,
                    ]
                )
            )
        );

        $handler = new AutoApplyCoupons(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(101);
        $product->method('get_parent_id')->willReturn(0);
        $product->method('get_price')->willReturnCallback(static fn (): string => '100');

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'get_subtotal',
                'get_cart',
                'get_applied_coupons',
                'has_discount',
                'apply_coupon',
                'remove_coupon',
            ])
            ->getMock();

        $cart->method('get_subtotal')->willReturn(100.0);
        $cart->method('get_applied_coupons')->willReturn([]);
        $cart->method('get_cart')->willReturn(
            [
                [
                    'data'     => $product,
                    'quantity' => 1,
                ],
            ]
        );
        $cart->expects(self::once())
            ->method('has_discount')
            ->with('AUTO30')
            ->willReturn(true);
        $cart->expects(self::never())->method('apply_coupon');
        $cart->expects(self::once())
            ->method('remove_coupon')
            ->with('AUTO30');

        $handler->maybeApplyCouponsToCart($cart);
    }
}
