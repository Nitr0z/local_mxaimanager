<?php

namespace local_mxaimanager\app\ai\provider\providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\chat_completion_request;
use local_mxaimanager\app\ai\provider\create_embedding_request;
use local_mxaimanager\app\ai\provider\create_speech_request;
use local_mxaimanager\app\ai\provider\create_transcription_request;
use local_mxaimanager\app\ai\provider\image_generation_request;
use local_mxaimanager\app\ai\provider\transcription;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\factory as base_factory;

class openai extends provider implements interfaces\chat_completion, interfaces\create_embedding,
                                         interfaces\create_image, interfaces\create_transcription,
                                         interfaces\create_speech
{
    private \curl $curl;
    private string $base_url;
    private string $api_key;
    private string $chat_model;
    private string $embedding_model;
    private string $image_model;
    private string $transcription_model;
    private string $tts_model;

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
        $this->transcription_model = $json_config['transcription_model'] ?? '';
        $this->tts_model = $json_config['tts_model'] ?? '';

        if (empty($this->base_url) || empty($this->api_key)) {
            throw new invalid_provider_instance_configuration('OpenAI is missing base url and/or api key');
        }

        $this->curl = $this->base_factory->curl();
        $this->curl->setHeader([
            "Authorization: Bearer {$this->api_key}",
            'Content-Type: application/json'
        ]);
    }

    private const CHAT_MODELS = [
        'gpt-5.4', 'gpt-5.4-pro', 'gpt-5', 'gpt-5-mini', 'gpt-5-nano',
        'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano',
        'gpt-4o', 'gpt-4o-mini',
        'o3', 'o3-mini', 'o3-pro',
    ];

    private const EMBEDDING_MODELS = [
        'text-embedding-3-large', 'text-embedding-3-small', 'text-embedding-ada-002',
    ];

    private const IMAGE_MODELS = [
        'gpt-image-1.5', 'gpt-image-1', 'gpt-image-1-mini',
        'dall-e-3', 'dall-e-2',
    ];

    private const TRANSCRIPTION_MODELS = [
        'gpt-4o-transcribe', 'gpt-4o-mini-transcribe',
        'gpt-4o-transcribe-diarize', 'whisper-1',
    ];

    private const TTS_MODELS = [
        'tts-1', 'tts-1-hd', 'gpt-4o-mini-tts',
    ];

    private static function add_chat_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'chat_model',
            'default_chat_model', 'openai_chat_model',
            interfaces\chat_completion::class, self::CHAT_MODELS);
    }

    private static function add_embedding_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'embedding_model',
            'default_embedding_model', 'openai_embedding_model',
            interfaces\create_embedding::class, self::EMBEDDING_MODELS);
    }

    private static function add_image_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'image_model',
            'default_image_model', 'openai_image_model',
            interfaces\create_image::class, self::IMAGE_MODELS);
    }

    private static function add_transcription_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'transcription_model',
            'default_transcription_model', 'openai_transcription_model',
            interfaces\create_transcription::class, self::TRANSCRIPTION_MODELS);
    }

    private static function add_tts_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        self::add_model_field($mform, $element_name_prefix, 'tts_model',
            'default_tts_model', 'openai_tts_model',
            interfaces\create_speech::class, self::TTS_MODELS);
    }

    public static function moodleform_definition(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        // Add base_url field
        $mform->addElement('text', "{$element_name_prefix}base_url", get_string('base_url', 'local_mxaimanager'));
        $mform->setType("{$element_name_prefix}base_url", PARAM_URL);
        $mform->setDefault("{$element_name_prefix}base_url", 'https://api.openai.com');

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

        // Add transcription model field
        self::add_transcription_model_field($mform, $element_name_prefix);

        // Add TTS model field
        self::add_tts_model_field($mform, $element_name_prefix);
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

        if (empty($data["{$element_name_prefix}transcription_model"])) {
            $errors["{$element_name_prefix}transcription_model"] = get_string('required');
        }

        if (empty($data["{$element_name_prefix}tts_model"])) {
            $errors["{$element_name_prefix}tts_model"] = get_string('required');
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
            case interfaces\create_transcription::class:
                self::add_transcription_model_field($mform, $element_name_prefix);
                break;
            case interfaces\create_speech::class:
                self::add_tts_model_field($mform, $element_name_prefix);
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
                throw new \Exception('Missing content in OpenAI response. OpenAI response: ' . $response);
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
                'Invalid response from OpenAI: ' . $t->getMessage(),
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
                throw new \Exception('Missing embedding data in OpenAI response. OpenAI response: ' . $response);
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
                'Invalid response from OpenAI: ' . $t->getMessage(),
                previous: $t
            );
        }
    }

    /**
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_image(
        string $prompt,
        bool $return_b64 = false
    ): image_generation_request {
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
                throw new \Exception('Missing image data in OpenAI response. OpenAI response: ' . $response);
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
                'Invalid response from OpenAI: ' . $t->getMessage(),
                previous: $t
            );
        }
    }

    /**
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_speech(
        string $input,
        string $voice = 'alloy',
        string $response_format = 'mp3'
    ): create_speech_request {
        if (empty($this->tts_model)) {
            throw new invalid_provider_instance_configuration('TTS model is not configured');
        }

        $content_type_map = [
            'mp3' => 'audio/mpeg',
            'opus' => 'audio/opus',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'wav' => 'audio/wav',
            'pcm' => 'audio/pcm',
        ];

        $payload = [
            'model' => $this->tts_model,
            'input' => $input,
            'voice' => $voice,
            'response_format' => $response_format,
        ];

        try {
            $response = $this->curl->post(
                "{$this->base_url}/audio/speech",
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            if (empty($response)) {
                throw new \Exception('Empty response from OpenAI TTS API');
            }

            // The response is raw audio binary, not JSON.
            // Check if it looks like a JSON error response.
            $first_char = substr($response, 0, 1);
            if ($first_char === '{') {
                $json = json_decode($response, true);
                if (isset($json['error'])) {
                    throw new \Exception('OpenAI TTS error: ' . ($json['error']['message'] ?? 'Unknown error'));
                }
            }

            $content_type = $content_type_map[$response_format] ?? 'audio/mpeg';

            // Estimate tokens: roughly 1 token per 4 characters of input.
            $estimated_input_tokens = (int) ceil(strlen($input) / 4);

            return new create_speech_request(
                $payload,
                $response,
                $content_type,
                $estimated_input_tokens,
                0
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from OpenAI TTS: ' . $t->getMessage(),
                previous: $t
            );
        }
    }

    /**
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_transcription(string $audio_filepath): create_transcription_request
    {
        if (empty($this->transcription_model)) {
            throw new invalid_provider_instance_configuration('Transcription model is not configured');
        }

        $fileData = [
            'file' => new \CURLFile($audio_filepath, mime_content_type($audio_filepath), basename($audio_filepath)),
            'model' => $this->transcription_model,
            'response_format' => 'verbose_json',
            'timestamp_granularities[]' => 'segment'
        ];

        // Temporarily remove content type headers for multipart upload
        $this->curl->resetHeader();
        $this->curl->setHeader([
            "Authorization: Bearer {$this->api_key}"
        ]);

        try {
            $response = $this->curl->post(
                "{$this->base_url}/v1/audio/transcriptions",
                $fileData
            );

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['text'])) {
                throw new \Exception('Missing text in OpenAI transcription response. OpenAI response: ' . $response);
            }

            $transcription = new transcription(
                $json['text'],
                $json['segments'] ?? []
            );

            return new create_transcription_request(
                $fileData,
                $json,
                $transcription,
                $json['usage']['prompt_tokens'] ?? 0,
                $json['usage']['completion_tokens'] ?? 0
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from OpenAI: ' . $t->getMessage(),
                previous: $t
            );
        } finally {
            // Reset headers back to JSON
            $this->curl->resetHeader();
            $this->curl->setHeader([
                "Authorization: Bearer {$this->api_key}",
                'Content-Type: application/json'
            ]);
        }
    }
}
