<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing;

use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Support\AbstractModule;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Modules\DynamicPricing\Application\RuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\RuleMatcher;
use Pluginora\Modules\DynamicPricing\Infrastructure\ScheduledRuleProcessor;
use Pluginora\Modules\DynamicPricing\Presentation\CartNoticeRenderer;
use Pluginora\Modules\DynamicPricing\Presentation\CartPriceAdjustments;
use Pluginora\Modules\DynamicPricing\Presentation\ProductPriceRenderer;
use Pluginora\Modules\DynamicPricing\Presentation\TierPricingTableRenderer;

final class Module extends AbstractModule
{
    public function getSlug(): string
    {
        return 'dynamic-pricing';
    }

    public function register(ContainerInterface $container): void
    {
        $container->share(RuleDataAccessor::class);
        $container->share(RuleMatcher::class);
        $container->share(ProductPricingService::class);
        $container->share(CartDiscountService::class);
        $container->share(ScheduledRuleProcessor::class);
        $container->share(ProductPriceRenderer::class);
        $container->share(CartPriceAdjustments::class);
        $container->share(CartNoticeRenderer::class);
        $container->share(TierPricingTableRenderer::class);
    }

    public function boot(ContainerInterface $container): void
    {
        $container->get(ScheduledRuleProcessor::class)->register();
        $container->get(ProductPriceRenderer::class)->register();
        $container->get(CartPriceAdjustments::class)->register();
        $container->get(CartNoticeRenderer::class)->register();
        $container->get(TierPricingTableRenderer::class)->register();
    }
}
