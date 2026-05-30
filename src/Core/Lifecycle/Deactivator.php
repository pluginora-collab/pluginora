<?php

declare(strict_types=1);

namespace Pluginora\Core\Lifecycle;

final class Deactivator
{
    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('pluginora_process_scheduled_rules');
        flush_rewrite_rules();
    }
}
