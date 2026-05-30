<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\CouponEngine\Application\AvailableCouponProvider;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;
use Pluginora\Modules\CouponEngine\Presentation\AvailableCouponsRenderer;
use Pluginora\Support\Rule;
use Pluginora\Support\RuleAction;

final class AvailableCouponProviderEdgeCaseIntegrationTest extends IntegrationTestCase
{
    public function test_provider_excludes_rules_without_coupon_code(): void
    {
        self::$ruleRepository->save(
            new Rule(
                null,
                'coupon_engine',
                'basic_coupon',
                'Missing Coupon Code',
                'active',
                10,
                null,
                null,
                null,
                [],
                [
                    new RuleAction('coupon_code', ''),
                    new RuleAction('display_locations', ['cart']),
                ]
            )
        );

        $provider = new AvailableCouponProvider(self::$ruleQueryRepository, new RuleDataAccessor());

        self::assertSame([], $provider->getForLocation('cart'));
    }

    public function test_renderer_outputs_nothing_when_no_rules_match_location(): void
    {
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBasicCouponPayload(
                    [
                        'coupon_code'       => 'ACCOUNT10',
                        'display_locations' => ['myaccount'],
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

        self::assertSame('', trim($output));
    }
}
