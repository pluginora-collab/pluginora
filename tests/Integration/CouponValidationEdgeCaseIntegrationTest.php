<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;
use Pluginora\Modules\CouponEngine\Presentation\CouponValidation;
use WC_Coupon;

final class CouponValidationEdgeCaseIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        if (
            ! class_exists('WC_Coupon')
            || ! function_exists('wc_clear_notices')
            || ! function_exists('wc_get_notices')
        ) {
            self::markTestSkipped('WooCommerce coupon APIs are required for coupon validation edge-case tests.');
        }

        wc_clear_notices();
    }

    public function test_validate_date_window_returns_false_when_coupon_is_already_invalid(): void
    {
        $coupon = $this->createManagedCoupon('ALREADYBAD');

        $validation = $this->makeValidation();

        self::assertFalse($validation->validateDateWindow(false, $coupon, null));
        self::assertSame([], wc_get_notices('error'));
    }

    public function test_validate_date_window_allows_unmanaged_coupon(): void
    {
        $coupon = $this->createManagedCoupon('UNMANAGED10');
        delete_post_meta($coupon->get_id(), '_pluginora_rule_id');

        $validation = $this->makeValidation();

        self::assertTrue($validation->validateDateWindow(true, $coupon, null));
        self::assertSame([], wc_get_notices('error'));
    }

    public function test_validate_date_window_allows_coupon_when_managed_rule_is_missing(): void
    {
        $coupon = $this->createManagedCoupon('MISSINGRULE');
        update_post_meta($coupon->get_id(), '_pluginora_rule_id', 999999);

        $validation = $this->makeValidation();

        self::assertTrue($validation->validateDateWindow(true, $coupon, null));
        self::assertSame([], wc_get_notices('error'));
    }

    public function test_validate_date_window_skips_frontend_notice_in_admin_context(): void
    {
        $expiredRuleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBasicCouponPayload(
                    [
                        'coupon_code' => 'ADMINEXPIRED',
                        'starts_at'   => gmdate('Y-m-d H:i:s', strtotime('-2 days')),
                        'ends_at'     => gmdate('Y-m-d H:i:s', strtotime('-1 day')),
                    ]
                )
            )
        );

        $expiredRule = self::$ruleRepository->find($expiredRuleId);
        self::$nativeCouponSyncService->sync($expiredRule);

        $coupon     = self::$nativeCouponSyncService->findCouponByCode('ADMINEXPIRED');
        $validation = $this->makeValidation(static fn (): bool => true);

        self::assertInstanceOf(WC_Coupon::class, $coupon);
        self::assertFalse($validation->validateDateWindow(true, $coupon, null));
        self::assertSame([], wc_get_notices('error'));
    }

    private function makeValidation(?\Closure $isAdminResolver = null): CouponValidation
    {
        return new CouponValidation(
            self::$ruleRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            $isAdminResolver
        );
    }

    private function createManagedCoupon(string $code): WC_Coupon
    {
        $coupon = new WC_Coupon();
        $coupon->set_code($code);
        $couponId = $coupon->save();

        return new WC_Coupon($couponId);
    }
}
