<?php

namespace local_mxaimanager\app\ai\feature;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\create_speech_request;
use local_mxaimanager\app\ai\provider\message;
use local_mxaimanager\app\ai\provider\create_transcription_request;
use local_mxaimanager\app\ai\provider\transcription;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\exceptions\no_provider_instance_configured;
use local_mxaimanager\app\factory as base_factory;

class handler
{
    private base_factory $base_factory;
    private provider_resolver $provider_resolver;
    private action_handler $action_handler;
    private entity $feature;

    public function __construct(base_factory $base_factory, entity $feature)
    {
        $this->base_factory = $base_factory;
        $this->feature = $feature;
        $this->provider_resolver = $this->base_factory->ai()->feature()->provider_resolver(
            $base_factory,
            $this->feature
        );
        $this->action_handler = $this->base_factory->ai()->feature()->action_handler($base_factory);
    }

    /**
     * @param message[] $messages
     * @param bool $json_mode Whether to enable JSON mode (forces the response to be valid JSON).
     * @param array|null $json_schema Optional JSON schema to enforce structured output (implies JSON mode).
     * @return string
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     * @throws no_provider_instance_configured
     */
    public function chat_completion(array $messages, bool $json_mode = false, ?array $json_schema = null): string
    {
        // Get provider_id and settings_json for the action.
        [$provider_id, $config_json] = $this->provider_resolver->get_provider_and_config(
            \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class
        );

        return $this->action_handler->chat_completion(
            $this->feature,
            $messages,
            $json_mode,
            $json_schema,
            $provider_id,
            $config_json
        );
    }

    /**
     * @param string $input
     * @param int $dimension
     * @return float[]
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     * @throws no_provider_instance_configured
     */
    public function create_embedding(string $input, int $dimension): array
    {
        // Get provider_id and settings_json for the action.
        [$provider_id, $config_json] = $this->provider_resolver->get_provider_and_config(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class
        );

        return $this->action_handler->create_embedding($this->feature, $input, $dimension, $provider_id, $config_json);
    }

    /**
     * @param string $prompt
     * @param bool $return_b64
     * @return string
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     * @throws no_provider_instance_configured
     */
    public function create_image(string $prompt, bool $return_b64 = false): string
    {
        // Get provider_id and settings_json for the action.
        [$provider_id, $config_json] = $this->provider_resolver->get_provider_and_config(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_image::class
        );

        return $this->action_handler->create_image(
            $this->feature,
            $prompt,
            $return_b64,
            $provider_id,
            $config_json
        );
    }

    /**
     * @param string $audio_filepath
     * @return transcription
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     * @throws no_provider_instance_configured
     */
    public function create_transcription(string $audio_filepath): transcription
    {
        // Get provider_id and settings_json for the action.
        [$provider_id, $config_json] = $this->provider_resolver->get_provider_and_config(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_transcription::class
        );

        return $this->action_handler->create_transcription(
            $this->feature,
            $audio_filepath,
            $provider_id,
            $config_json
        );
    }

    /**
     * @param string $input The text to synthesize.
     * @param string $voice The voice to use.
     * @param string $response_format The audio format.
     * @return create_speech_request
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     * @throws no_provider_instance_configured
     */
    public function create_speech(
        string $input,
        string $voice = 'alloy',
        string $response_format = 'mp3'
    ): create_speech_request {
        // Get provider_id and settings_json for the action.
        [$provider_id, $config_json] = $this->provider_resolver->get_provider_and_config(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_speech::class
        );

        return $this->action_handler->create_speech(
            $this->feature,
            $input,
            $voice,
            $response_format,
            $provider_id,
            $config_json
        );
    }
}
