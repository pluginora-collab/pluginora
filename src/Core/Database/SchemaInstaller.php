<?php

declare(strict_types=1);

namespace Pluginora\Core\Database;

use wpdb;

final class SchemaInstaller
{
    public const VERSION = '1.0.5';

    private const VERSION_OPTION = 'pluginora_db_version';

    public function __construct(
        private readonly wpdb $wpdb,
        private readonly RuleTables $tables
    ) {
    }

    public function install(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($this->getSchemas() as $schema) {
            dbDelta($schema->getSql());
            $this->wpdb->query(sprintf('ALTER TABLE %s ENGINE=InnoDB', $schema->getName()));
        }

        update_option(self::VERSION_OPTION, self::VERSION);
    }

    public function getInstalledVersion(): string
    {
        $version = get_option(self::VERSION_OPTION, '');

        return is_string($version) ? $version : '';
    }

    public function needsUpgrade(): bool
    {
        $installedVersion = $this->getInstalledVersion();

        if ('' === $installedVersion) {
            return true;
        }

        return version_compare($installedVersion, self::VERSION, '<');
    }

    public function installOrUpgrade(): void
    {
        if (! $this->needsUpgrade()) {
            return;
        }

        $this->install();
    }

    /**
     * @return TableSchema[]
     */
    public function getSchemas(): array
    {
        $charsetCollate = $this->wpdb->get_charset_collate();

        return [
            new TableSchema(
                $this->tables->rules(),
                "CREATE TABLE {$this->tables->rules()} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    module VARCHAR(50) NOT NULL,
                    rule_type VARCHAR(50) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'inactive',
                    priority INT UNSIGNED NOT NULL DEFAULT 1,
                    stack_mode_override VARCHAR(50) NULL,
                    starts_at_gmt DATETIME NULL,
                    ends_at_gmt DATETIME NULL,
                    created_at_gmt DATETIME NOT NULL,
                    updated_at_gmt DATETIME NOT NULL,
                    PRIMARY KEY  (id),
                    KEY module (module),
                    KEY rule_type (rule_type),
                    KEY status (status),
                    KEY priority (priority),
                    KEY starts_at_gmt (starts_at_gmt),
                    KEY ends_at_gmt (ends_at_gmt)
                ) {$charsetCollate};"
            ),
            new TableSchema(
                $this->tables->conditions(),
                "CREATE TABLE {$this->tables->conditions()} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    rule_id BIGINT UNSIGNED NOT NULL,
                    condition_type VARCHAR(50) NOT NULL,
                    operator VARCHAR(20) NOT NULL,
                    condition_value LONGTEXT NOT NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id),
                    KEY rule_id (rule_id),
                    KEY condition_type (condition_type)
                ) {$charsetCollate};"
            ),
            new TableSchema(
                $this->tables->actions(),
                "CREATE TABLE {$this->tables->actions()} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    rule_id BIGINT UNSIGNED NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    action_value LONGTEXT NOT NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id),
                    KEY rule_id (rule_id),
                    KEY action_type (action_type)
                ) {$charsetCollate};"
            ),
            new TableSchema(
                $this->tables->items(),
                "CREATE TABLE {$this->tables->items()} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    rule_id BIGINT UNSIGNED NOT NULL,
                    object_type VARCHAR(50) NOT NULL,
                    object_id BIGINT UNSIGNED NOT NULL,
                    PRIMARY KEY  (id),
                    KEY rule_id (rule_id),
                    KEY object_type (object_type),
                    KEY object_id (object_id)
                ) {$charsetCollate};"
            ),
            new TableSchema(
                $this->tables->tiers(),
                "CREATE TABLE {$this->tables->tiers()} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    rule_id BIGINT UNSIGNED NOT NULL,
                    min_qty INT UNSIGNED NOT NULL,
                    max_qty INT UNSIGNED NULL,
                    discount_type VARCHAR(20) NOT NULL,
                    discount_value DECIMAL(18,4) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id),
                    KEY rule_id (rule_id),
                    KEY min_qty (min_qty),
                    KEY max_qty (max_qty)
                ) {$charsetCollate};"
            ),
            new TableSchema(
                $this->tables->logs(),
                "CREATE TABLE {$this->tables->logs()} (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    rule_id BIGINT UNSIGNED NOT NULL,
                    context_type VARCHAR(50) NOT NULL,
                    context_reference VARCHAR(191) NOT NULL,
                    message TEXT NOT NULL,
                    created_at_gmt DATETIME NOT NULL,
                    PRIMARY KEY  (id),
                    KEY rule_id (rule_id),
                    KEY context_type (context_type),
                    KEY created_at_gmt (created_at_gmt)
                ) {$charsetCollate};"
            ),
        ];
    }
}
