<?php

namespace local_mxaimanager\output\manage_features;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core\output\named_templatable;
use renderable;
use renderer_base;
use local_mxaimanager\app\ai\feature\entity;
use local_mxaimanager\app\factory as base_factory;
use local_mxaimanager\app\quota_helper;

class edit implements named_templatable, renderable
{
    private base_factory $base_factory;
    private \moodleform $form;
    private entity $feature;

    public function __construct(base_factory $base_factory, \moodleform $form, entity $feature)
    {
        $this->base_factory = $base_factory;
        $this->form = $form;
        $this->feature = $feature;
    }

    public function get_template_name(renderer_base $renderer): string
    {
        return 'local_mxaimanager/manage_features/edit';
    }

    public function export_for_template(renderer_base $output): array
    {
        return array_merge([
            'features' => true,
            'name' => get_string($this->feature->get_name_identifier(), $this->feature->get_component()),
            'form_html' => $this->form->render(),
        ], quota_helper::get_quota_bars());
    }
}
