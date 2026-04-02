<?php

namespace local_mxaimanager\app\ai\provider\providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\chat_completion_request;
use local_mxaimanager\app\ai\provider\create_embedding_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\factory as base_factory;

class ollama extends provider implements interfaces\chat_completion, interfaces\create_embedding
{
    private \curl $curl;
    private string $base_url;
    private string $api_key;
    private string $chat_model;
    private string $embedding_model;

    /**
     * @throws invalid_provider_instance_configuration
     */
    public function __construct(base_factory $base_factory, array $json_config)
    {
        $this->base_factory = $base_factory;
        $this->base_url = $json_config['base_url'] ?? '';
        $this->api_key = $json_config['api_key'] ?? '';
        $this->chat_model = $json_config['chat_model'] ?? '';
        $this->embedding_model = $json_config['embedding_model'] ?? '';

        if (empty($this->base_url) || empty($this->api_key)) {
            throw new invalid_provider_instance_configuration('Ollama is missing base url and/or api key');
        }

        $this->curl = $this->base_factory->curl();
        $this->curl->setHeader([
            "Authorization: Bearer {$this->api_key}",
            'Content-Type: application/json'
        ]);
    }

    private const CHAT_MODELS = [
        'llama3.1', 'llama3', 'llama3.2', 'mistral', 'phi3', 'gemma2',
        'codellama', 'qwen2', 'deepseek-r1',
    ];

    private const EMBEDDING_MODELS = [
        'nomic-embed-text', 'mxbai-embed-large', 'all-minilm', 'snowflake-arctic-embed',
    ];

    private static function add_chat_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'chat_model',
            'default_chat_model', 'ollama_chat_model',
            interfaces\chat_completion::class, self::CHAT_MODELS);
    }

    private static function add_embedding_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'embedding_model',
            'default_embedding_model', 'ollama_embedding_model',
            interfaces\create_embedding::class, self::EMBEDDING_MODELS);
    }

    public static function moodleform_definition(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        // Add base_url field
        $mform->addElement('text', "{$element_name_prefix}base_url", get_string('base_url', 'local_mxaimanager'));
        $mform->setType("{$element_name_prefix}base_url", PARAM_URL);
        $mform->setDefault("{$element_name_prefix}base_url", '');

        // Add api_key field
        $mform->addElement('text', "{$element_name_prefix}api_key", get_string('api_key', 'local_mxaimanager'));
        $mform->setType("{$element_name_prefix}api_key", PARAM_TEXT);
        $mform->setDefault("{$element_name_prefix}api_key", '');

        // Add chat model field
        self::add_chat_model_field($mform, $element_name_prefix);

        // Add embedding model field
        self::add_embedding_model_field($mform, $element_name_prefix);
    }

    public static function moodleform_validation(array $data, string $element_name_prefix): array
    {
        $errors = [];

        if (empty($data["{$element_name_prefix}base_url"])) {
            $errors["{$element_name_prefix}base_url"] = get_string('required');
        }

        if (empty($data["{$element_name_prefix}api_key"])) {
            $errors["{$element_name_prefix}api_key"] = get_string('required');
        }

        if (empty($data["{$element_name_prefix}chat_model"])) {
            $errors["{$element_name_prefix}chat_model"] = get_string('required');
        }

        if (empty($data["{$element_name_prefix}embedding_model"])) {
            $errors["{$element_name_prefix}embedding_model"] = get_string('required');
        }

        return $errors;
    }

    public static function action_moodleform_definition(
        \MoodleQuickForm $mform,
        string $interface,
        string $element_name_prefix
    ): void {
        switch ($interface) {
            case interfaces\chat_completion::class:
                self::add_chat_model_field($mform, $element_name_prefix);
                break;
            case interfaces\create_embedding::class:
                self::add_embedding_model_field($mform, $element_name_prefix);
                break;
            default:
        }
    }

    /**
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function chat_completion(
        array $messages,
        bool $json_mode = false,
        ?array $json_schema = null
    ): chat_completion_request {
        if (empty($this->chat_model)) {
            throw new invalid_provider_instance_configuration('Chat model is not configured');
        }

        $payload = [
            'model' => $this->chat_model,
            'stream' => false,
            'messages' => $messages,
        ];

        if ($json_schema !== null) {
            $payload['format'] = $json_schema;
        } elseif ($json_mode) {
            $payload['format'] = 'json';
        }

        try {
            $response = $this->curl->post("{$this->base_url}/api/chat", json_encode($payload, JSON_THROW_ON_ERROR));

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['message']['content'])) {
                throw new \Exception('Missing content in Ollama response. Ollama response: ' . $response);
            }

            return new chat_completion_request(
                $payload,
                $json,
                $json['message']['content'],
                $json['prompt-eval-count'],
                $json['eval-count']
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from Ollama: ' . $t->getMessage(),
                previous: $t
            );
        }
    }

    /**
     * @throws invalid_provider_instance_response
     * @throws invalid_provider_instance_configuration
     */
    public function get_embedding(string $input, ?int $dimension): create_embedding_request
    {
        if (empty($this->embedding_model)) {
            throw new invalid_provider_instance_configuration('Embedding model is not configured');
        }

        $payload = [
            'model' => $this->embedding_model,
            'input' => $input,
            "options" => [
                'dimensions' => $dimension
            ]
        ];

        try {
            $response = $this->curl->post("{$this->base_url}/api/embed", json_encode($payload, JSON_THROW_ON_ERROR));

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['embeddings'][0])) {
                throw new \Exception('Missing embedding data in Ollama response. Ollama response: ' . $response);
            }

            return new create_embedding_request(
                $payload,
                $json,
                $json['embeddings'][0],
                $json['prompt_eval_count'],
                0 // Ollama does not provide completion eval count for embeddings
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from Ollama: ' . $t->getMessage(),
                previous: $t
            );
        }
    }
}
