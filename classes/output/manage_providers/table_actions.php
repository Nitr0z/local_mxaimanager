<?php

namespace local_mxaimanager\output\manage_providers;

use renderer_base;

class table_actions implements \renderable, \core\output\named_templatable
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function get_template_name(renderer_base $renderer): string
    {
        return 'local_mxaimanager/manage_providers/table_actions';
    }

    public function export_for_template(renderer_base $output): array
    {
        $is_preconfigured = $this->id < 0;
        $is_managed = \local_mxaimanager\app\ai\feature\action_handler::is_managed_provider($this->id);
        return [
            'id' => $this->id,
            'is_preconfigured' => $is_preconfigured,
            'is_managed' => $is_managed,
        ];
    }
}
