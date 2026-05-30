<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Modules\DynamicPricing\Application\RuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\RuleMatcher;
use Pluginora\Modules\DynamicPricing\Presentation\CartPriceAdjustments;
use WC_Cart;
use WC_Product;

final class CartPriceAdjustmentsIntegrationTest extends IntegrationTestCase
{
    private CartPriceAdjustments $adjustments;

    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Cart') || ! class_exists('WC_Product')) {
            self::markTestSkipped('WooCommerce cart APIs are required for cart price adjustment integration tests.');
        }

        $ruleDataAccessor = new RuleDataAccessor();
        $ruleMatcher = new RuleMatcher($ruleDataAccessor);

        $this->adjustments = new CartPriceAdjustments(
            new ProductPricingService(self::$ruleQueryRepository, $ruleMatcher, $ruleDataAccessor),
            new CartDiscountService(self::$ruleQueryRepository, $ruleMatcher, $ruleDataAccessor),
            self::$conflictResolver
        );
    }

    public function test_apply_cart_fee_adds_negative_fee_for_matching_cart_discount(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeCartSubtotalDiscountPayload()));

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_subtotal', 'get_cart', 'get_applied_coupons', 'add_fee'])
            ->getMock();

        $cart->method('get_subtotal')->willReturn(150.0);
        $cart->method('get_cart')->willReturn([]);
        $cart->method('get_applied_coupons')->willReturn([]);
        $cart->expects(self::once())
            ->method('add_fee')
            ->with('Integration Cart Discount', -20.0, false);

        $this->adjustments->applyCartFee($cart);
    }

    public function test_render_cart_item_subtotal_outputs_discounted_markup_and_savings_message(): void
    {
        $html = $this->adjustments->renderCartItemSubtotal(
            '<span>$200</span>',
            [
                'quantity' => 2,
                'pluginora_pricing' => [
                    'original_price'          => 100.0,
                    'discounted_price'        => 80.0,
                    'savings_message_enabled' => true,
                ],
            ],
            'abc'
        );

        self::assertStringContainsString('pluginora-cart-price', $html);
        self::assertStringContainsString('pluginora-savings-message', $html);
        self::assertStringContainsString('You saved', wp_strip_all_tags($html));
    }

    public function test_apply_item_prices_sets_discounted_price_and_metadata(): void
    {
        update_option(
            SettingsRepository::OPTION_KEY,
            ['conflict_mode' => 'stack_all']
        );

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'                  => 'active',
                        'applies_to'              => 'selected_products',
                        'selected_products'       => [111],
                        'discount_value'          => 20,
                        'savings_message_enabled' => true,
                    ]
                )
            )
        );

        $product = $this->createProduct('Cart Discount Product', '100', 111);
        $cart = $this->createMutableCart($product, 1, 100.0);

        $this->adjustments->applyItemPrices($cart);

        self::assertSame('80', $product->get_price('edit'));
        self::assertSame(100.0, $cart->cart_contents['line-item']['pluginora_original_price']);
        self::assertSame(
            80.0,
            $cart->cart_contents['line-item']['pluginora_pricing']['discounted_price']
        );
        self::assertTrue($cart->cart_contents['line-item']['pluginora_pricing']['savings_message_enabled']);
    }

    public function test_apply_item_prices_restores_original_price_when_coupon_priority_blocks_dynamic_pricing(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'coupon_priority']);

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeAutoApplyCouponPayload(
                    [
                        'selected_products' => [111],
                        'coupon_amount'     => 25,
                    ]
                )
            )
        );

        $product = $this->createProduct('Restored Product', '80', 111);
        $cart = $this->createMutableCart($product, 1, 100.0, true);

        $this->adjustments->applyItemPrices($cart);

        self::assertSame('100', $product->get_price('edit'));
        self::assertSame(100.0, $cart->cart_contents['line-item']['pluginora_original_price']);
        self::assertArrayNotHasKey('pluginora_pricing', $cart->cart_contents['line-item']);
    }

    private function createProduct(string $name, string $price, int $id): WC_Product
    {
        $currentPrice = $price;

        $product = $this->getMockBuilder(WC_Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_id', 'get_parent_id', 'get_price', 'set_price', 'get_name'])
            ->getMock();
        $product->method('get_id')->willReturn($id);
        $product->method('get_parent_id')->willReturn(0);
        $product->method('get_name')->willReturn($name);
        $product->method('get_price')->willReturnCallback(static function (...$args) use (&$currentPrice): string {
            unset($args);

            return $currentPrice;
        });
        $product->method('set_price')->willReturnCallback(static function ($newPrice) use (&$currentPrice): void {
            $currentPrice = (string) $newPrice;
        });

        return $product;
    }

    private function createMutableCart(
        WC_Product $product,
        int $quantity,
        float $subtotal,
        bool $withExistingPricing = false
    ): WC_Cart {
        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_subtotal', 'get_cart', 'get_applied_coupons', 'add_fee'])
            ->getMock();

        $cart->cart_contents = [
            'line-item' => [
                'data'     => $product,
                'quantity' => $quantity,
            ],
        ];

        if ($withExistingPricing) {
            $cart->cart_contents['line-item']['pluginora_original_price'] = 100.0;
            $cart->cart_contents['line-item']['pluginora_pricing'] = [
                'original_price'          => 100.0,
                'discounted_price'        => 80.0,
                'savings_message_enabled' => true,
            ];
        }

        $cart->method('get_subtotal')->willReturn($subtotal);
        $cart->method('get_cart')->willReturnCallback(fn (): array => $cart->cart_contents);
        $cart->method('get_applied_coupons')->willReturn([]);
        $cart->expects(self::never())->method('add_fee');

        return $cart;
    }
}
