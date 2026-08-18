<?php

namespace local_mxaimanager\app\ai\provider\providers\interfaces;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\vision_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;

/**
 * Provider capability: read images with a vision-capable model.
 */
interface vision
{
    /**
     * @param string $prompt Instruction sent with the images.
     * @param string[] $image_filepaths Local JPEG/PNG paths.
     * @return vision_request
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function vision(string $prompt, array $image_filepaths): vision_request;
}
