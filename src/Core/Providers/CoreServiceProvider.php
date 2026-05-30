<?php

declare(strict_types=1);

namespace Pluginora\Core\Providers;

use Pluginora\Core\Application\ConflictResolver;
use Pluginora\Core\Database\RuleTables;
use Pluginora\Core\Database\SchemaInstaller;
use Pluginora\Core\Compatibility\WooCommerceGuard;
use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Core\Support\AbstractServiceProvider;
use Pluginora\Core\Support\PluginContext;
use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Repository\Contracts\RuleRepositoryInterface;
use Pluginora\Repository\Wpdb\WpdbRuleQueryRepository;
use Pluginora\Repository\Wpdb\WpdbRuleRepository;
use wpdb;

final class CoreServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->instance(wpdb::class, $GLOBALS['wpdb']);

        $container->share(
            WooCommerceGuard::class,
            static fn (ContainerInterface $app): WooCommerceGuard => new WooCommerceGuard(
                $app->get(PluginContext::class)
            )
        );

        $container->share(
            RuleTables::class,
            static fn (ContainerInterface $app): RuleTables => new RuleTables(
                $app->get(wpdb::class)->prefix
            )
        );

        $container->share(SchemaInstaller::class);
        $container->share(WpdbRuleRepository::class);
        $container->share(
            RuleRepositoryInterface::class,
            static fn (ContainerInterface $app): WpdbRuleRepository => $app->get(WpdbRuleRepository::class)
        );
        $container->share(WpdbRuleQueryRepository::class);
        $container->share(
            RuleQueryRepositoryInterface::class,
            static fn (ContainerInterface $app): WpdbRuleQueryRepository => $app->get(WpdbRuleQueryRepository::class)
        );

        $container->share(SettingsRepository::class);
        $container->share(ConflictResolver::class);
    }
}
