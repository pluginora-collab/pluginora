<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\CouponEngine\Application\NativeCouponSyncService;

final class CouponSyncIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Coupon')) {
            self::markTestSkipped('WooCommerce is required for coupon sync integration tests.');
        }
    }

    public function test_sync_creates_native_coupon_and_meta(): void
    {
        $ruleId = self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeBasicCouponPayload()));
        $rule   = self::$ruleRepository->find($ruleId);

        self::$nativeCouponSyncService->sync($rule);

        $coupon = self::$nativeCouponSyncService->findCouponByCode('SAVE15');

        self::assertNotNull($coupon);
        self::assertSame('save15', $coupon->get_code());
        self::assertSame('percent', $coupon->get_discount_type());
        self::assertSame('15', (string) $coupon->get_amount());
        self::assertSame('Save fifteen percent', $coupon->get_description());
        self::assertSame([101], array_map('intval', $coupon->get_product_ids()));
        self::assertSame([12], array_map('intval', $coupon->get_product_categories()));
        self::assertSame(
            (string) $ruleId,
            (string) get_post_meta($coupon->get_id(), NativeCouponSyncService::META_RULE_ID, true)
        );
        self::assertSame('yes', get_post_meta($coupon->get_id(), NativeCouponSyncService::META_MANAGED, true));
        self::assertSame('publish', get_post_status($coupon->get_id()));
    }

    public function test_set_status_and_delete_update_coupon_post_state(): void
    {
        $ruleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBasicCouponPayload(
                    [
                        'coupon_code' => 'STATUS10',
                        'status'      => 'inactive',
                    ]
                )
            )
        );
        $rule = self::$ruleRepository->find($ruleId);

        self::$nativeCouponSyncService->sync($rule);

        $coupon = self::$nativeCouponSyncService->findCouponByCode('STATUS10');

        self::assertNotNull($coupon);
        self::assertSame('draft', get_post_status($coupon->get_id()));

        self::$nativeCouponSyncService->setStatus($rule->withStatus('active'));
        self::assertSame('publish', get_post_status($coupon->get_id()));

        self::$nativeCouponSyncService->delete($rule->withStatus('active'));
        self::assertSame('trash', get_post_status($coupon->get_id()));
    }
}
