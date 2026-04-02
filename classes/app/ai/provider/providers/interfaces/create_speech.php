<?php

namespace local_mxaimanager\app\ai\provider\providers\interfaces;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\create_speech_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;

interface create_speech
{
    /**
     * @param string $input The text to synthesize into speech.
     * @param string $voice The voice to use (provider-specific).
     * @param string $response_format The audio format (mp3, opus, aac, flac, wav, pcm).
     * @return create_speech_request
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_speech(
        string $input,
        string $voice = 'alloy',
        string $response_format = 'mp3'
    ): create_speech_request;
}
