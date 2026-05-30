<?php

declare(strict_types=1);

namespace Pluginora\Core\Compatibility;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

final class HposCompatibility
{
    public function __construct(private readonly string $pluginFile)
    {
    }

    public function declare(): void
    {
        if (! class_exists(FeaturesUtil::class)) {
            return;
        }

        FeaturesUtil::declare_compatibility('custom_order_tables', $this->pluginFile, true);
    }
}
