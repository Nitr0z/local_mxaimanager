<?php

namespace local_mxaimanager\output\manage_providers;

use renderer_base;

class table_actions implements \renderable, \core\output\named_templatable
{
    private int $id;
    private bool $ai_pack_owned;

    public function __construct(int $id, bool $ai_pack_owned = true)
    {
        $this->id = $id;
        $this->ai_pack_owned = $ai_pack_owned;
    }

    public function get_template_name(renderer_base $renderer): string
    {
        return 'local_mxaimanager/manage_providers/table_actions';
    }

    public function export_for_template(renderer_base $output): array
    {
        $is_preconfigured = $this->id < 0;
        return [
            'id' => $this->id,
            'is_preconfigured' => $is_preconfigured,
            'ai_pack_owned' => $this->ai_pack_owned,
        ];
    }
}
