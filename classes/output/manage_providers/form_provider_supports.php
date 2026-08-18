<?php

namespace local_mxaimanager\output\manage_providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core\output\named_templatable;
use renderable;
use renderer_base;

class form_provider_supports implements named_templatable, renderable
{
    /**
     * @var class-string
     */
    private string $provider;

    /**
     * @param class-string $provider
     */
    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    public function get_template_name(renderer_base $renderer): string
    {
        return 'local_mxaimanager/manage_providers/form_provider_supports';
    }

    public function export_for_template(renderer_base $output): array
    {
        $implemented_classes = class_implements($this->provider);

        return [
            'chat' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class,
                $implemented_classes,
                true
            ),
            'embedding' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class,
                $implemented_classes,
                true
            ),
            'image' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_image::class,
                $implemented_classes,
                true
            ),
            'transcription' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_transcription::class,
                $implemented_classes,
                true
            ),
            'audio' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_audio::class,
                $implemented_classes,
                true
            ),
            'vision' => in_array(
                \local_mxaimanager\app\ai\provider\providers\interfaces\vision::class,
                $implemented_classes,
                true
            ),
        ];
    }
}
