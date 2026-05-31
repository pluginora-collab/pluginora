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

require_once __DIR__ . '/src/Core/Database/RuleTables.php';

global $wpdb;

$pluginoraTables = new \Pluginora\Core\Database\RuleTables($wpdb->prefix);

foreach ($pluginoraTables->dropStatements() as $pluginoraStatement) {
    $wpdb->query($pluginoraStatement);
}

delete_option('pluginora_version');
delete_option('pluginora_db_version');
delete_option('pluginora_settings');
