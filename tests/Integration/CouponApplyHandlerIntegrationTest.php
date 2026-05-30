<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\CouponEngine\Presentation\CouponApplyHandler;
use WC_Cart;

final class CouponApplyHandlerIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Cart')) {
            self::markTestSkipped('WooCommerce cart APIs are required for coupon apply handler integration tests.');
        }
    }

    public function test_process_request_applies_coupon_and_returns_redirect_url(): void
    {
        $handler = new CouponApplyHandler();
        $cart    = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['apply_coupon'])
            ->getMock();

        $cart->expects(self::once())
            ->method('apply_coupon')
            ->with('save15');

        $redirectUrl = $handler->processRequest(
            'POST',
            [
                'pluginora_apply_coupon' => 'SAVE15',
                'pluginora_coupon_nonce' => wp_create_nonce('pluginora_apply_coupon_save15'),
            ],
            $cart,
            'https://example.com/cart'
        );

        self::assertSame('https://example.com/cart', $redirectUrl);
    }

    public function test_process_request_rejects_invalid_nonce(): void
    {
        $handler = new CouponApplyHandler();
        $cart    = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['apply_coupon'])
            ->getMock();

        $cart->expects(self::never())->method('apply_coupon');

        $redirectUrl = $handler->processRequest(
            'POST',
            [
                'pluginora_apply_coupon' => 'save15',
                'pluginora_coupon_nonce' => 'invalid-nonce',
            ],
            $cart,
            'https://example.com/cart'
        );

        self::assertNull($redirectUrl);
    }
}
