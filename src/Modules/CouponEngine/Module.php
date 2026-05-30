<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine;

use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Support\AbstractModule;
use Pluginora\Modules\CouponEngine\Application\AvailableCouponProvider;
use Pluginora\Modules\CouponEngine\Application\CouponSavingsEstimator;
use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Application\NativeCouponSyncService;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;
use Pluginora\Modules\CouponEngine\Infrastructure\CouponRuleSyncHooks;
use Pluginora\Modules\CouponEngine\Presentation\AutoApplyCoupons;
use Pluginora\Modules\CouponEngine\Presentation\AvailableCouponsRenderer;
use Pluginora\Modules\CouponEngine\Presentation\BogoCartManager;
use Pluginora\Modules\CouponEngine\Presentation\CouponApplyHandler;
use Pluginora\Modules\CouponEngine\Presentation\CouponValidation;

final class Module extends AbstractModule
{
    public function getSlug(): string
    {
        return 'coupon-engine';
    }

    public function register(ContainerInterface $container): void
    {
        $container->share(RuleDataAccessor::class);
        $container->share(CouponRuleMatcher::class);
        $container->share(NativeCouponSyncService::class);
        $container->share(CouponSavingsEstimator::class);
        $container->share(AvailableCouponProvider::class);
        $container->share(CouponRuleSyncHooks::class);
        $container->share(AutoApplyCoupons::class);
        $container->share(BogoCartManager::class);
        $container->share(AvailableCouponsRenderer::class);
        $container->share(CouponApplyHandler::class);
        $container->share(CouponValidation::class);
    }

    public function boot(ContainerInterface $container): void
    {
        $container->get(CouponRuleSyncHooks::class)->register();
        $container->get(AutoApplyCoupons::class)->register();
        $container->get(BogoCartManager::class)->register();
        $container->get(AvailableCouponsRenderer::class)->register();
        $container->get(CouponApplyHandler::class)->register();
        $container->get(CouponValidation::class)->register();
    }
}
