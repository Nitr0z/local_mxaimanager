<?php

namespace local_mxaimanager\output\manage;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core\output\named_templatable;
use renderable;
use renderer_base;
use local_mxaimanager\app\factory as base_factory;
use local_mxaimanager\app\quota_helper;

class index implements named_templatable, renderable
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    public function get_template_name(renderer_base $renderer): string
    {
        return 'local_mxaimanager/manage/index';
    }

    public function export_for_template(renderer_base $output): array
    {
        $no_providers = $this->base_factory->ai()->provider()->repository()->get_all()->empty();
        $no_default_providers = $this->base_factory->ai()->default_provider()->repository()->get_all()->empty();

        // Check if freemium is enabled but not configured.
        $freemium_enabled = !empty(get_config('local_mxaimanager', 'enable_freemium'));
        $freemium_api_key = get_config('local_mxaimanager', 'freemium_api_key');
        $freemium_not_configured = $freemium_enabled && empty($freemium_api_key);

        return array_merge([
            'general' => true,
            'no_providers' => $no_providers,
            'no_default_providers' => $no_default_providers,
            'freemium_not_configured' => $freemium_not_configured,
            'freemium_settings_url' => (new \moodle_url('/admin/settings.php', [
                'section' => 'local_mxaimanager_freemium'
            ]))->out(false),
        ], quota_helper::get_quota_bars());
    }
}
