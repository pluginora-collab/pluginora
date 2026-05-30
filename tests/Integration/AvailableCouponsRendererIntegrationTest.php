<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\CouponEngine\Application\AvailableCouponProvider;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;
use Pluginora\Modules\CouponEngine\Presentation\AvailableCouponsRenderer;

final class AvailableCouponsRendererIntegrationTest extends IntegrationTestCase
{
    public function test_provider_filters_rules_by_display_location(): void
    {
        self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeBasicCouponPayload()));
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBasicCouponPayload(
                    [
                        'name'              => 'Checkout Only Coupon',
                        'coupon_code'       => 'CHECKOUT20',
                        'display_locations' => ['checkout'],
                    ]
                )
            )
        );

        $provider = new AvailableCouponProvider(self::$ruleQueryRepository, new RuleDataAccessor());
        $rules    = $provider->getForLocation('cart');

        self::assertCount(1, $rules);
        self::assertSame('SAVE15', (new RuleDataAccessor())->getActionValue($rules[0], 'coupon_code', ''));
    }

    public function test_render_outputs_only_matching_coupon_cards(): void
    {
        self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeBasicCouponPayload()));
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBasicCouponPayload(
                    [
                        'name'                  => 'Checkout Only Coupon',
                        'coupon_code'           => 'CHECKOUT20',
                        'coupon_description'    => 'Checkout only discount',
                        'display_locations'     => ['checkout'],
                    ]
                )
            )
        );

        $ruleDataAccessor = new RuleDataAccessor();
        $renderer = new AvailableCouponsRenderer(
            new AvailableCouponProvider(self::$ruleQueryRepository, $ruleDataAccessor),
            $ruleDataAccessor
        );

        ob_start();
        $renderer->render('cart');
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Available Coupons', $output);
        self::assertStringContainsString('SAVE15', $output);
        self::assertStringContainsString('Save fifteen percent', $output);
        self::assertStringContainsString('pluginora_apply_coupon', $output);
        self::assertStringNotContainsString('CHECKOUT20', $output);
        self::assertStringNotContainsString('Checkout only discount', $output);
    }
}
