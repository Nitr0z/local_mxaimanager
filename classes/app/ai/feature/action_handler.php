<?php

namespace local_mxaimanager\app\ai\feature;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\create_speech_request;
use local_mxaimanager\app\ai\provider\message;
use local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_image;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_speech;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_transcription;
use local_mxaimanager\app\ai\provider\transcription;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\exceptions\quota_exceeded_exception;
use local_mxaimanager\app\factory as base_factory;

class action_handler
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    /**
     * Check token quotas (daily/weekly/monthly × input/output).
     * Quotas only apply to the built-in freemium provider (preconfigured, ID < 0).
     * Client-configured providers are unlimited.
     *
     * @param int $provider_id The provider being used for this request.
     * @throws quota_exceeded_exception
     */
    private function check_quotas(int $provider_id): void
    {
        // Quotas only apply to preconfigured providers (freemium).
        // Client-added providers (positive IDs) are unlimited.
        if ($provider_id > 0) {
            return;
        }

        $now = time();

        // Period definitions: config key prefix => start timestamp.
        $periods = [
            'daily' => mktime(0, 0, 0, (int)date('n', $now), (int)date('j', $now), (int)date('Y', $now)),
            'weekly' => strtotime('monday this week', $now),
            'monthly' => mktime(0, 0, 0, (int)date('n', $now), 1, (int)date('Y', $now)),
        ];

        foreach ($periods as $period => $start) {
            $input_quota = (int) get_config('local_mxaimanager', "{$period}_input_quota");
            $output_quota = (int) get_config('local_mxaimanager', "{$period}_output_quota");

            // Skip this period if both are unlimited.
            if ($input_quota <= 0 && $output_quota <= 0) {
                continue;
            }

            $row = $this->base_factory->db()->get_record_sql(
                'SELECT COALESCE(SUM(input_tokens), 0) AS used_input,
                        COALESCE(SUM(output_tokens), 0) AS used_output
                   FROM {local_mxaimanager_feature_action_usage_logs}
                  WHERE timecreated >= :start',
                ['start' => $start]
            );

            if ($input_quota > 0 && (int)$row->used_input >= $input_quota) {
                throw new quota_exceeded_exception($period, 'input');
            }
            if ($output_quota > 0 && (int)$row->used_output >= $output_quota) {
                throw new quota_exceeded_exception($period, 'output');
            }
        }
    }

    /**
     * @param int $provider_id
     * @param array $config_json
     * @return chat_completion|create_embedding
     */
    protected function get_provider_handler_provider_and_settings_json(
        int $provider_id,
        array $config_json
    ): mixed {
        // Get the provider.
        $provider = $this->base_factory->ai()->provider()->repository()->get_by_id($provider_id);

        // Get the provider handler classname.
        $provider_handler_classname = $provider->get_classname();

        // Create the provider handler.
        return new $provider_handler_classname($this->base_factory, $config_json);
    }

    /**
     * @param entity $feature
     * @param message[] $messages
     * @param bool $json_mode Whether to enable JSON mode (forces the response to be valid JSON).
     * @param array|null $json_schema Optional JSON schema to enforce structured output (implies JSON mode).
     * @param int $provider_id
     * @param array $config_json
     * @return string
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function chat_completion(
        entity $feature,
        array $messages,
        bool $json_mode,
        ?array $json_schema,
        int $provider_id,
        array $config_json
    ): string {
        $this->check_quotas($provider_id);

        // Get the provider handler.
        $handler = $this->get_provider_handler_provider_and_settings_json(
            $provider_id,
            $config_json
        );

        // Validate interface.
        if (!($handler instanceof chat_completion)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' does not support chat completion'
            );
        }

        // Make the chat completion request.
        $chat_completion_request = $handler->chat_completion($messages, $json_mode, $json_schema);

        // Log the request and response.
        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode($chat_completion_request->get_request_json(), JSON_THROW_ON_ERROR),
            'response_json' => json_encode($chat_completion_request->get_response_json(), JSON_THROW_ON_ERROR),
            'input_tokens' => $chat_completion_request->get_input_tokens(),
            'output_tokens' => $chat_completion_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->base_factory->user()->id,
            'timecreated' => time(),
        ]);

        // Return the response.
        return $chat_completion_request->get_response();
    }

    /**
     * @param entity $feature
     * @param string $input
     * @param int $dimension
     * @param int $provider_id
     * @param array $config_json
     * @return float[]
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_embedding(
        entity $feature,
        string $input,
        int $dimension,
        int $provider_id,
        array $config_json
    ): array {
        $this->check_quotas($provider_id);

        // Get the provider handler.
        $handler = $this->get_provider_handler_provider_and_settings_json(
            $provider_id,
            $config_json
        );

        // Validate interface.
        if (!($handler instanceof create_embedding)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' does not support embedding creation'
            );
        }

        // Make the chat request.
        $create_embedding_request = $handler->get_embedding($input, $dimension);

        // Log the request and response.
        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode($create_embedding_request->get_request_json(), JSON_THROW_ON_ERROR),
            'response_json' => json_encode($create_embedding_request->get_response_json(), JSON_THROW_ON_ERROR),
            'input_tokens' => $create_embedding_request->get_input_tokens(),
            'output_tokens' => $create_embedding_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->base_factory->user()->id,
            'timecreated' => time(),
        ]);

        // Return the response.
        return $create_embedding_request->get_response();
    }

    /**
     * @param entity $feature
     * @param string $prompt
     * @param bool $return_b64 Whether to return the image as a base64 string. Default is false. If false, returns a URL to the image instead.
     * @param int $provider_id
     * @param array $config_json
     * @return string
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_image(
        entity $feature,
        string $prompt,
        bool $return_b64,
        int $provider_id,
        array $config_json
    ): string {
        $this->check_quotas($provider_id);

        // Get the provider handler.
        $handler = $this->get_provider_handler_provider_and_settings_json(
            $provider_id,
            $config_json
        );

        // Validate interface.
        if (!($handler instanceof create_image)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' does not support image creation'
            );
        }

        // Make the image creation request.
        $create_image_request = $handler->create_image($prompt, $return_b64);

        // Log the request and response.
        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode($create_image_request->get_request_json(), JSON_THROW_ON_ERROR),
            'response_json' => json_encode($create_image_request->get_response_json(), JSON_THROW_ON_ERROR),
            'input_tokens' => $create_image_request->get_input_tokens(),
            'output_tokens' => $create_image_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->base_factory->user()->id,
            'timecreated' => time(),
        ]);

        return $create_image_request->get_response();
    }

    /**
     * @param entity $feature
     * @param string $audio_filepath
     * @param int $provider_id
     * @param array $config_json
     * @return transcription
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_transcription(
        entity $feature,
        string $audio_filepath,
        int $provider_id,
        array $config_json
    ): transcription {
        $this->check_quotas($provider_id);

        // Get the provider handler.
        $handler = $this->get_provider_handler_provider_and_settings_json(
            $provider_id,
            $config_json
        );

        // Validate interface.
        if (!($handler instanceof create_transcription)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' does not support transcription creation'
            );
        }

        // Make the transcription request.
        $create_transcription_request = $handler->create_transcription($audio_filepath);

        // Log the request and response.
        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode($create_transcription_request->get_request_json(), JSON_THROW_ON_ERROR),
            'response_json' => json_encode($create_transcription_request->get_response_json(), JSON_THROW_ON_ERROR),
            'input_tokens' => $create_transcription_request->get_input_tokens(),
            'output_tokens' => $create_transcription_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->base_factory->user()->id,
            'timecreated' => time(),
        ]);

        return $create_transcription_request->get_response();
    }

    /**
     * @param entity $feature
     * @param string $input The text to synthesize.
     * @param string $voice The voice to use.
     * @param string $response_format The audio format.
     * @param int $provider_id
     * @param array $config_json
     * @return create_speech_request
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_speech(
        entity $feature,
        string $input,
        string $voice,
        string $response_format,
        int $provider_id,
        array $config_json
    ): create_speech_request {
        $this->check_quotas($provider_id);

        // Get the provider handler.
        $handler = $this->get_provider_handler_provider_and_settings_json(
            $provider_id,
            $config_json
        );

        // Validate interface.
        if (!($handler instanceof create_speech)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' does not support speech synthesis'
            );
        }

        // Make the speech synthesis request.
        $create_speech_request = $handler->create_speech($input, $voice, $response_format);

        // Log the request and response.
        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode($create_speech_request->get_request_json(), JSON_THROW_ON_ERROR),
            'response_json' => json_encode($create_speech_request->jsonSerialize(), JSON_THROW_ON_ERROR),
            'input_tokens' => $create_speech_request->get_input_tokens(),
            'output_tokens' => $create_speech_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->base_factory->user()->id,
            'timecreated' => time(),
        ]);

        return $create_speech_request;
    }
}
