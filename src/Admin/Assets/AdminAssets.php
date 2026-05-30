<?php

declare(strict_types=1);

namespace Pluginora\Admin\Assets;

use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Core\Support\PluginContext;

final class AdminAssets implements HookableInterface
{
    public function __construct(private readonly PluginContext $context)
    {
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hookSuffix): void
    {
        if (false === strpos($hookSuffix, 'pluginora')) {
            return;
        }

        wp_enqueue_style(
            'pluginora-admin',
            $this->context->getPluginUrl() . 'assets/admin/css/admin.css',
            [],
            $this->context->getVersion()
        );

        wp_enqueue_script(
            'pluginora-admin',
            $this->context->getPluginUrl() . 'assets/admin/js/admin.js',
            [],
            $this->context->getVersion(),
            true
        );

        wp_add_inline_script(
            'pluginora-admin',
            'window.pluginoraAdmin = ' . wp_json_encode(
                [
                    'restBase' => esc_url_raw(rest_url('pluginora/v1/')),
                    'nonce'    => wp_create_nonce('wp_rest'),
                    'strings'  => [
                        'loading'         => __('Loading Pluginora...', 'pluginora'),
                        'loadError'       => __('Pluginora could not load the admin experience.', 'pluginora'),
                        'saveSuccess'     => __('Rule saved successfully.', 'pluginora'),
                        'deleteConfirm'   => __('Delete this rule?', 'pluginora'),
                        'cancel'          => __('Cancel', 'pluginora'),
                        'save'            => __('Save Rule', 'pluginora'),
                        'update'          => __('Update Rule', 'pluginora'),
                        'createRule'      => __('Create Rule', 'pluginora'),
                        'existingRules'   => __('Existing Rules', 'pluginora'),
                        'searchPlaceholder' => __('Search products or categories...', 'pluginora'),
                    ],
                ]
            ) . ';',
            'before'
        );
    }
}
