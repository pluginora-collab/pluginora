<?php

declare(strict_types=1);

namespace Pluginora\Admin\Providers;

use Pluginora\Admin\Api\LookupsController;
use Pluginora\Admin\Api\RulesController;
use Pluginora\Admin\Assets\AdminAssets;
use Pluginora\Admin\Forms\RulePayloadMapper;
use Pluginora\Admin\Forms\RuleSchemaProvider;
use Pluginora\Admin\Pages\RuleBuilderPage;
use Pluginora\Admin\Settings\PluginSettingsPage;
use Pluginora\Core\Contracts\ContainerInterface;
use Pluginora\Core\Support\AbstractServiceProvider;

final class AdminServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->share(RuleSchemaProvider::class);
        $container->share(RulePayloadMapper::class);
        $container->share(AdminAssets::class);
        $container->share(RuleBuilderPage::class);
        $container->share(PluginSettingsPage::class);
        $container->share(RulesController::class);
        $container->share(LookupsController::class);
    }

    public function boot(ContainerInterface $container): void
    {
        $container->get(RulesController::class)->register();
        $container->get(LookupsController::class)->register();

        if (is_admin()) {
            $container->get(AdminAssets::class)->register();
            $container->get(RuleBuilderPage::class)->register();
            $container->get(PluginSettingsPage::class)->register();
        }
    }
}
