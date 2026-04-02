<?php

namespace local_mxaimanager\app\ai\provider\providers;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\chat_completion_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\factory as base_factory;

/**
 * Freemium provider — chat-only, configurable via admin settings.
 * Credentials are stored in Moodle config (database), not in source code.
 */
class freemium extends provider implements interfaces\chat_completion
{
    private \curl $curl;
    private string $base_url;
    private string $api_key;
    private string $chat_model;

    /**
     * @throws invalid_provider_instance_configuration
     */
    public function __construct(base_factory $base_factory, array $json_config)
    {
        $this->base_factory = $base_factory;
        $this->base_url = rtrim($json_config['base_url'] ?? '', '/');
        $this->api_key = $json_config['api_key'] ?? '';
        $this->chat_model = $json_config['chat_model'] ?? '';

        if (empty($this->base_url) || empty($this->api_key) || empty($this->chat_model)) {
            throw new invalid_provider_instance_configuration(
                'Freemium provider is not fully configured. Please set API key, base URL, and model in the admin settings.'
            );
        }

        $this->curl = $this->base_factory->curl();
        $this->curl->setHeader([
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
        ]);
    }

    public static function moodleform_definition(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        // No configurable fields — managed via admin settings page.
    }

    public static function moodleform_validation(array $data, string $element_name_prefix): array
    {
        return [];
    }

    public static function action_moodleform_definition(
        \MoodleQuickForm $mform,
        string $interface,
        string $element_name_prefix
    ): void {
        // No configurable fields.
    }

    /**
     * @throws invalid_provider_instance_response
     */
    public function chat_completion(
        array $messages,
        bool $json_mode = false,
        ?array $json_schema = null
    ): chat_completion_request {
        $payload = [
            'model'    => $this->chat_model,
            'messages' => $messages,
        ];

        if ($json_schema !== null) {
            $payload['response_format'] = [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'response_schema',
                    'schema' => $json_schema,
                ],
            ];
        } elseif ($json_mode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = $this->curl->post(
                $this->base_url . '/chat/completions',
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['choices'][0]['message']['content'])) {
                throw new \Exception('Missing content in Freemium response: ' . $response);
            }

            // Strip Qwen3 <think>...</think> blocks from the response.
            $content = $json['choices'][0]['message']['content'];
            $content = preg_replace('/<think>[\s\S]*?<\/think>\s*/u', '', $content);
            $content = trim($content);

            return new chat_completion_request(
                $payload,
                $json,
                $content,
                $json['usage']['prompt_tokens'] ?? 0,
                $json['usage']['completion_tokens'] ?? 0
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from Freemium provider: ' . $t->getMessage(),
                previous: $t
            );
        }
    }
}
