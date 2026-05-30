<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Presentation\CouponValidation;
use WC_Coupon;

final class CouponValidationIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        if (
            ! class_exists('WC_Coupon')
            || ! function_exists('wc_clear_notices')
            || ! function_exists('wc_get_notices')
        ) {
            self::markTestSkipped('WooCommerce coupon APIs are required for coupon validation integration tests.');
        }

        wc_clear_notices();
    }

    public function test_validate_date_window_rejects_expired_managed_coupon(): void
    {
        $expiredRuleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBasicCouponPayload(
                    [
                        'coupon_code' => 'EXPIRED15',
                        'starts_at'   => gmdate('Y-m-d H:i:s', strtotime('-2 days')),
                        'ends_at'     => gmdate('Y-m-d H:i:s', strtotime('-1 day')),
                    ]
                )
            )
        );
        $expiredRule = self::$ruleRepository->find($expiredRuleId);

        self::$nativeCouponSyncService->sync($expiredRule);

        $coupon     = self::$nativeCouponSyncService->findCouponByCode('EXPIRED15');
        $validation = new CouponValidation(
            self::$ruleRepository,
            new CouponRuleMatcher(new \Pluginora\Modules\CouponEngine\Application\RuleDataAccessor())
        );

        self::assertInstanceOf(WC_Coupon::class, $coupon);
        self::assertFalse($validation->validateDateWindow(true, $coupon, null));

        $errors = wc_get_notices('error');

        self::assertNotEmpty($errors);
        self::assertSame('This coupon is not active right now.', wp_strip_all_tags((string) $errors[0]['notice']));
    }

    public function test_validate_date_window_allows_current_managed_coupon(): void
    {
        $activeRuleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBasicCouponPayload(
                    [
                        'coupon_code' => 'ACTIVE15',
                        'starts_at'   => gmdate('Y-m-d H:i:s', strtotime('-1 hour')),
                        'ends_at'     => gmdate('Y-m-d H:i:s', strtotime('+1 day')),
                    ]
                )
            )
        );
        $activeRule = self::$ruleRepository->find($activeRuleId);

        self::$nativeCouponSyncService->sync($activeRule);

        $coupon     = self::$nativeCouponSyncService->findCouponByCode('ACTIVE15');
        $validation = new CouponValidation(
            self::$ruleRepository,
            new CouponRuleMatcher(new \Pluginora\Modules\CouponEngine\Application\RuleDataAccessor())
        );

        self::assertInstanceOf(WC_Coupon::class, $coupon);
        self::assertTrue($validation->validateDateWindow(true, $coupon, null));
        self::assertSame([], wc_get_notices('error'));
    }
}
