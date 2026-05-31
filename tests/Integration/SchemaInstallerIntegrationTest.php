<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Core\Database\SchemaInstaller;

final class SchemaInstallerIntegrationTest extends IntegrationTestCase
{
    public function test_needs_upgrade_is_false_for_current_schema(): void
    {
        global $wpdb;

        $installer = new SchemaInstaller($wpdb, self::$tables);

        self::assertFalse($installer->needsUpgrade());
        self::assertSame(SchemaInstaller::VERSION, $installer->getInstalledVersion());
    }

    public function test_install_or_upgrade_updates_older_schema_version(): void
    {
        global $wpdb;

        update_option('pluginora_db_version', '1.0.0');

        $installer = new SchemaInstaller($wpdb, self::$tables);

        self::assertTrue($installer->needsUpgrade());

        $installer->installOrUpgrade();

        self::assertSame(SchemaInstaller::VERSION, $installer->getInstalledVersion());
        self::assertFalse($installer->needsUpgrade());
    }
}
