<?php

namespace local_mxaimanager\output\manage_features;

use renderer_base;
use local_mxaimanager\app\ai\feature\action\entity;
use local_mxaimanager\app\factory as base_factory;

class table_ai_actions implements \renderable, \core\output\named_templatable
{
    private base_factory $base_factory;
    private int $feature_id;

    public function __construct(base_factory $base_factory, int $feature_id)
    {
        $this->base_factory = $base_factory;
        $this->feature_id = $feature_id;
    }

    public function get_template_name(renderer_base $renderer): string
    {
        return 'local_mxaimanager/manage_features/table_ai_actions';
    }

    public function export_for_template(renderer_base $output): array
    {
        $used_interfaces = $this->base_factory->ai()->feature()->action()->repository()
            ->get_all_by_feature_id($this->feature_id)
            ->map(static function (entity $action) {
                return $action->get_action_interface();
            })->to_array();

        return [
            'chat' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class,
                $used_interfaces,
                true
            ),
            'embedding' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class,
                $used_interfaces,
                true
            ),
            'image' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_image::class,
                $used_interfaces,
                true
            ),
            'transcription' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_transcription::class,
                $used_interfaces,
                true
            ),
            'audio' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_audio::class,
                $used_interfaces,
                true
            ),
        ];
    }
}
