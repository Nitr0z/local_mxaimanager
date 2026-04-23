<?php

namespace local_mxaimanager\app\ai\provider\providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\chat_completion_request;
use local_mxaimanager\app\ai\provider\create_embedding_request;
use local_mxaimanager\app\ai\provider\image_generation_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\factory as base_factory;

class nebius extends provider implements interfaces\chat_completion, interfaces\create_embedding,
                                         interfaces\create_image
{
    private \curl $curl;
    private string $base_url;
    private string $api_key;
    private string $chat_model;
    private string $embedding_model;
    private string $image_model;

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
        $this->image_model = $json_config['image_model'] ?? '';

        if (empty($this->base_url) || empty($this->api_key)) {
            throw new invalid_provider_instance_configuration('Nebius is missing base url and/or api key');
        }

        $this->curl = $this->base_factory->curl();
        $this->curl->setHeader([
            "Authorization: Bearer {$this->api_key}",
            'Content-Type: application/json'
        ]);
    }

    private const CHAT_MODELS = [
        'meta-llama/Meta-Llama-3.1-70B-Instruct', 'meta-llama/Meta-Llama-3.1-8B-Instruct',
        'Qwen/Qwen2.5-72B-Instruct', 'deepseek-ai/DeepSeek-V3',
        'mistralai/Mistral-Nemo-Instruct-2407',
    ];

    private const EMBEDDING_MODELS = [
        'BAAI/bge-en-icl', 'intfloat/multilingual-e5-large-instruct', 'BAAI/bge-m3',
    ];

    private const IMAGE_MODELS = [
        'black-forest-labs/FLUX.1-schnell', 'stability-ai/sdxl',
    ];

    private static function add_chat_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'chat_model',
            'default_chat_model', 'nebius_chat_model',
            interfaces\chat_completion::class, self::CHAT_MODELS);
    }

    private static function add_embedding_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'embedding_model',
            'default_embedding_model', 'nebius_embedding_model',
            interfaces\create_embedding::class, self::EMBEDDING_MODELS);
    }

    private static function add_image_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'image_model',
            'default_image_model', 'nebius_image_model',
            interfaces\create_image::class, self::IMAGE_MODELS);
    }

    public static function moodleform_definition(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        // Add base_url field
        $mform->addElement('text', "{$element_name_prefix}base_url", get_string('base_url', 'local_mxaimanager'));
        $mform->setType("{$element_name_prefix}base_url", PARAM_URL);
        $mform->setDefault("{$element_name_prefix}base_url", 'https://api.tokenfactory.nebius.com');

        // Add api_key field
        $mform->addElement('text', "{$element_name_prefix}api_key", get_string('api_key', 'local_mxaimanager'));
        $mform->setType("{$element_name_prefix}api_key", PARAM_TEXT);
        $mform->setDefault("{$element_name_prefix}api_key", '');

        // Add chat model field
        self::add_chat_model_field($mform, $element_name_prefix);

        // Add embedding model field
        self::add_embedding_model_field($mform, $element_name_prefix);

        // Add image model field
        self::add_image_model_field($mform, $element_name_prefix);

        // Add credit multiplier field.
        self::add_credit_multiplier_field($mform, $element_name_prefix);
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

        if (empty($data["{$element_name_prefix}image_model"])) {
            $errors["{$element_name_prefix}image_model"] = get_string('required');
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
            case interfaces\create_image::class:
                self::add_image_model_field($mform, $element_name_prefix);
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
            'messages' => $messages,
        ];

        if ($json_schema !== null) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'response_schema',
                    'schema' => $json_schema
                ],
            ];
        } elseif ($json_mode) {
            $payload['response_format'] = [
                'type' => 'json_object',
            ];
        }

        try {
            $response = $this->curl->post(
                "{$this->base_url}/v1/chat/completions",
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['choices'][0]['message']['content'])) {
                throw new \Exception('Missing content in Nebius response. Nebius response: ' . $response);
            }

            return new chat_completion_request(
                $payload,
                $json,
                $json['choices'][0]['message']['content'],
                $json['usage']['prompt_tokens'],
                $json['usage']['completion_tokens']
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from Nebius: ' . $t->getMessage(),
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
            'dimensions' => $dimension
        ];

        try {
            $response = $this->curl->post(
                "{$this->base_url}/v1/embeddings",
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['data'][0]['embedding'])) {
                throw new \Exception('Missing embedding data in Nebius response. Nebius response: ' . $response);
            }

            return new create_embedding_request(
                $payload,
                $json,
                $json['data'][0]['embedding'],
                $json['usage']['prompt_tokens'],
                $json['usage']['total_tokens']
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from Nebius: ' . $t->getMessage(),
                previous: $t
            );
        }
    }

    /**
     * @param string $prompt
     * @param bool $return_b64
     * @return image_generation_request
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_image(string $prompt, bool $return_b64 = false): image_generation_request
    {
        if (empty($this->image_model)) {
            throw new invalid_provider_instance_configuration('Image model is not configured');
        }

        $payload = [
            'model' => $this->image_model,
            'prompt' => $prompt,
            'response_format' => $return_b64 ? 'b64_json' : 'url',
        ];

        try {
            $response = $this->curl->post(
                "{$this->base_url}/v1/images/generations",
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['data'][0][$return_b64 ? 'b64_json' : 'url'])) {
                throw new \Exception('Missing image data in Nebius response. Nebius response: ' . $response);
            }

            return new image_generation_request(
                $payload,
                $json,
                $json['data'][0][$return_b64 ? 'b64_json' : 'url'],
                0,
                0
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from Nebius: ' . $t->getMessage(),
                previous: $t
            );
        }
    }
}
