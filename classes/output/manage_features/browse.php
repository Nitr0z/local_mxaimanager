<?php

namespace local_mxaimanager\output\manage_features;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core\output\named_templatable;
use renderable;
use renderer_base;
use local_mxaimanager\app\factory as base_factory;
use local_mxaimanager\app\quota_helper;

class browse implements named_templatable, renderable
{
    private base_factory $base_factory;
    private \moodle_url $url;

    public function __construct(
        base_factory $base_factory,
        \moodle_url $url,
    ) {
        $this->base_factory = $base_factory;
        $this->url = $url;
    }

    public function get_template_name(renderer_base $renderer): string
    {
        return 'local_mxaimanager/manage_features/browse';
    }

    public function export_for_template(renderer_base $output): array
    {
        $table = new table($this->base_factory, $this->url);

        return array_merge([
            'features' => true,
            'table_html' => $table->get_html(25),
        ], quota_helper::get_quota_bars());
    }
}
