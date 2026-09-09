<?php

namespace local_mxaimanager\app\ai\feature;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\message;
use local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_audio;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_image;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_transcription;
use local_mxaimanager\app\ai\provider\providers\interfaces\vision;
use local_mxaimanager\app\ai\provider\transcription;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\factory as base_factory;

class action_handler
{
    private base_factory $base_factory;

    /**
     * Maximum number of continuation attempts when a chat completion response
     * is truncated (finish_reason = 'length') during JSON mode requests.
     */
    public const MAX_CONTINUATION_ATTEMPTS = 10;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    /**
     * @return int
     */
    private function current_user_id(): int
    {
        return (int) ($this->base_factory->user()->id ?? 0);
    }

    /**
     * @param int $provider_id
     * @param array $config_json
     * @return chat_completion|create_embedding
     * @throws invalid_provider_instance_configuration
     */
    protected function get_provider_handler_provider_and_settings_json(
        int $provider_id,
        array $config_json
    ): mixed {
        // Get the provider.
        $provider = $this->base_factory->ai()->provider()->repository()->get_by_id($provider_id);

        // Get the provider handler classname.
        $provider_handler_classname = $provider->get_classname();

        // The stored classname can point at a provider that is no longer installed.
        // Fail with the documented exception instead of a fatal "class not found".
        if (!class_exists($provider_handler_classname)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' refers to an unknown provider class: '
                . $provider_handler_classname
            );
        }

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
        $this->log_usage($feature, $chat_completion_request);

        // If the response was truncated (finish_reason = 'length') and we are in JSON mode,
        // continue fetching until we get a complete response or hit the max attempts.
        $is_json_request = $json_mode || $json_schema !== null;
        if ($is_json_request && $chat_completion_request->get_finish_reason() === 'length') {
            $response = $this->continue_truncated_response(
                $feature,
                $handler,
                $messages,
                $json_mode,
                $json_schema,
                $chat_completion_request
            );
            return $this->strip_markdown_json_wrapper($response);
        }

        // Return the response, stripping any markdown code block wrapper for JSON requests.
        $response = $chat_completion_request->get_response();
        return $is_json_request ? $this->strip_markdown_json_wrapper($response) : $response;
    }

    /**
     * Continue fetching a truncated chat completion response until the provider
     * returns a non-'length' finish_reason or we hit the max continuation attempts.
     *
     * @param entity $feature
     * @param chat_completion $handler
     * @param message[] $messages The original messages array.
     * @param bool $json_mode
     * @param array|null $json_schema
     * @param \local_mxaimanager\app\ai\provider\chat_completion_request $initial_request The first (truncated) response.
     * @return string The concatenated full response content.
     * @throws invalid_provider_instance_response
     */
    private function continue_truncated_response(
        entity $feature,
        chat_completion $handler,
        array $messages,
        bool $json_mode,
        ?array $json_schema,
        \local_mxaimanager\app\ai\provider\chat_completion_request $initial_request
    ): string {
        $accumulated_response = $initial_request->get_response();
        $last_request = $initial_request;

        for ($attempt = 0; $attempt < self::MAX_CONTINUATION_ATTEMPTS; $attempt++) {
            // Append the partial assistant response to the conversation.
            $messages[] = new message('assistant', $last_request->get_response());
            $messages[] = new message(
                'user',
                'Continue exactly from where you left off. Output ONLY the remaining part of the JSON. Do not repeat any previous content, do not add explanations, and do not start a new JSON object.'
            );

            // Make the continuation request.
            $last_request = $handler->chat_completion($messages, $json_mode, $json_schema);

            // Log each continuation call individually for accurate token tracking.
            $this->log_usage($feature, $last_request);

            // Accumulate the response content.
            $accumulated_response .= $last_request->get_response();

            // If the response is no longer truncated, we're done.
            if ($last_request->get_finish_reason() !== 'length') {
                break;
            }
        }

        return $accumulated_response;
    }

    /**
     * Log a chat completion request/response to the usage logs.
     *
     * @param entity $feature
     * @param \local_mxaimanager\app\ai\provider\chat_completion_request $chat_completion_request
     */
    private function log_usage(
        entity $feature,
        \local_mxaimanager\app\ai\provider\chat_completion_request $chat_completion_request
    ): void {
        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode($chat_completion_request->get_request_json(), JSON_THROW_ON_ERROR),
            'response_json' => json_encode($chat_completion_request->get_response_json(), JSON_THROW_ON_ERROR),
            'input_tokens' => $chat_completion_request->get_input_tokens(),
            'output_tokens' => $chat_completion_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->current_user_id(),
            'timecreated' => time(),
        ]);
    }

    /**
     * Strip markdown code block wrappers (```json ... ``` or ``` ... ```) from a response string.
     *
     * Some models wrap their JSON output in markdown code fences even when JSON mode is enabled.
     * This method removes those wrappers so the consumer receives pure JSON.
     *
     * @param string $response The raw response string.
     * @return string The response with markdown code block wrapper removed, if present.
     */
    private function strip_markdown_json_wrapper(string $response): string
    {
        $trimmed = trim($response);

        if (preg_match('/^```(?:json)?\s*\n(.*)\n```$/s', $trimmed, $matches)) {
            return trim($matches[1]);
        }

        return $response;
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
            'user_id' => $this->current_user_id(),
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
            'user_id' => $this->current_user_id(),
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
            'user_id' => $this->current_user_id(),
            'timecreated' => time(),
        ]);

        return $create_transcription_request->get_response();
    }

    /**
     * @param entity $feature
     * @param string $text The text to synthesize.
     * @param int $provider_id
     * @param array $config_json
     * @return string Base64-encoded audio.
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_audio(
        entity $feature,
        string $text,
        int $provider_id,
        array $config_json
    ): string {
        // Get the provider handler.
        $handler = $this->get_provider_handler_provider_and_settings_json(
            $provider_id,
            $config_json
        );

        // Validate interface.
        if (!($handler instanceof create_audio)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' does not support audio creation'
            );
        }

        // Make the audio creation request.
        $create_audio_request = $handler->create_audio($text);

        // Log the request and response.
        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode($create_audio_request->get_request_json(), JSON_THROW_ON_ERROR),
            'response_json' => json_encode($create_audio_request->get_response_json(), JSON_THROW_ON_ERROR),
            'input_tokens' => $create_audio_request->get_input_tokens(),
            'output_tokens' => $create_audio_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->current_user_id(),
            'timecreated' => time(),
        ]);

        return $create_audio_request->get_response();
    }

    /**
     * @param entity $feature
     * @param string $prompt
     * @param string[] $image_filepaths
     * @param int $provider_id
     * @param array $config_json
     * @return string
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function vision(
        entity $feature,
        string $prompt,
        array $image_filepaths,
        int $provider_id,
        array $config_json
    ): string {
        $handler = $this->get_provider_handler_provider_and_settings_json(
            $provider_id,
            $config_json
        );

        if (!($handler instanceof vision)) {
            throw new invalid_provider_instance_configuration(
                'Provider instance ID: ' . $provider_id . ' does not support vision'
            );
        }

        $vision_request = $handler->vision($prompt, $image_filepaths);

        $this->base_factory->db()->insert_record('local_mxaimanager_feature_action_usage_logs', [
            'feature_id' => $feature->get_id(),
            'request_json' => json_encode(
                $this->redact_vision_request_for_log($vision_request->get_request_json()),
                JSON_THROW_ON_ERROR
            ),
            'response_json' => json_encode($vision_request->get_response_json(), JSON_THROW_ON_ERROR),
            'input_tokens' => $vision_request->get_input_tokens(),
            'output_tokens' => $vision_request->get_output_tokens(),
            'session_id' => session_id(),
            'user_id' => $this->current_user_id(),
            'timecreated' => time(),
        ]);

        return $vision_request->get_response();
    }

    /**
     * Drop raw image bytes from usage logs.
     *
     * @param array $request_json
     * @return array
     */
    private function redact_vision_request_for_log(array $request_json): array
    {
        if (!isset($request_json['messages']) || !is_array($request_json['messages'])) {
            return $request_json;
        }

        foreach ($request_json['messages'] as &$message) {
            if (isset($message['images']) && is_array($message['images'])) {
                $message['images'] = array_fill(0, count($message['images']), '[image]');
            }
            if (!isset($message['content']) || !is_array($message['content'])) {
                continue;
            }
            foreach ($message['content'] as &$part) {
                if (($part['type'] ?? '') === 'image_url') {
                    $part['image_url']['url'] = '[image]';
                }
            }
            unset($part);
        }
        unset($message);

        return $request_json;
    }
}
