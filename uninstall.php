<?php

/**
 * Uninstall Pluginora.
 *
 * @package Pluginora
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('pluginora_version');
delete_option('pluginora_db_version');
