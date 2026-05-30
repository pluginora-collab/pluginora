<?php

declare(strict_types=1);

namespace Pluginora\Core\Support;

final class PluginContext
{
    public function __construct(
        private readonly string $pluginFile,
        private readonly string $pluginBasename,
        private readonly string $pluginPath,
        private readonly string $pluginUrl,
        private readonly string $version,
        private readonly string $textDomain
    ) {
    }

    public static function fromFile(string $pluginFile, string $version, string $textDomain): self
    {
        return new self(
            $pluginFile,
            plugin_basename($pluginFile),
            trailingslashit(plugin_dir_path($pluginFile)),
            trailingslashit(plugin_dir_url($pluginFile)),
            $version,
            $textDomain
        );
    }

    public function getPluginFile(): string
    {
        return $this->pluginFile;
    }

    public function getPluginBasename(): string
    {
        return $this->pluginBasename;
    }

    public function getPluginPath(): string
    {
        return $this->pluginPath;
    }

    public function getPluginUrl(): string
    {
        return $this->pluginUrl;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getTextDomain(): string
    {
        return $this->textDomain;
    }

    public function getLanguagesRelativePath(): string
    {
        return dirname($this->pluginBasename) . '/languages';
    }
}
