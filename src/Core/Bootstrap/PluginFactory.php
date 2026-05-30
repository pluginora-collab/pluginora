<?php

declare(strict_types=1);

namespace Pluginora\Core\Bootstrap;

use Pluginora\Admin\Providers\AdminServiceProvider;
use Pluginora\Core\Compatibility\WooCommerceGuard;
use Pluginora\Core\Container\Container;
use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Providers\CoreServiceProvider;
use Pluginora\Core\Support\PluginContext;
use Pluginora\Frontend\Providers\FrontendServiceProvider;
use Pluginora\Modules\CouponEngine\Module as CouponEngineModule;
use Pluginora\Modules\DynamicPricing\Module as DynamicPricingModule;

final class PluginFactory
{
    public static function create(string $pluginFile, string $version, string $textDomain): Plugin
    {
        $context   = PluginContext::fromFile($pluginFile, $version, $textDomain);
        $container = new Container();
        $loader    = new Loader($container);
        $core      = new CoreServiceProvider();

        $container->instance(ContainerInterface::class, $container);
        $container->instance(PluginContext::class, $context);

        $core->register($container);

        $loader->addProvider($core);
        $loader->addProvider(new AdminServiceProvider());
        $loader->addProvider(new FrontendServiceProvider());
        $loader->addModule(new DynamicPricingModule());
        $loader->addModule(new CouponEngineModule());

        return new Plugin(
            $context,
            $loader,
            $container->get(WooCommerceGuard::class)
        );
    }
}
