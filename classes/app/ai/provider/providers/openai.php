<?php

namespace local_mxaimanager\app\ai\provider\providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\chat_completion_request;
use local_mxaimanager\app\ai\provider\create_audio_request;
use local_mxaimanager\app\ai\provider\create_embedding_request;
use local_mxaimanager\app\ai\provider\create_transcription_request;
use local_mxaimanager\app\ai\provider\image_generation_request;
use local_mxaimanager\app\ai\provider\transcription;
use local_mxaimanager\app\ai\provider\vision_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use local_mxaimanager\app\factory as base_factory;

class openai extends provider implements interfaces\chat_completion, interfaces\create_embedding,
                                         interfaces\create_image, interfaces\create_transcription,
                                         interfaces\create_audio, interfaces\vision
{
    private const TTS_ALLOWED_VOICES = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    private const TTS_ALLOWED_FORMATS = ['mp3', 'opus', 'aac', 'flac', 'wav', 'pcm'];
    private const TTS_DEFAULT_VOICE = 'alloy';
    private const TTS_DEFAULT_FORMAT = 'mp3';

    private \curl $curl;
    private string $base_url;
    private string $api_key;
    private string $chat_model;
    private string $embedding_model;
    private string $image_model;
    private string $transcription_model;
    private string $tts_model;
    private string $tts_voice;
    private string $tts_format;
    private string $vision_model;

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
        $this->tts_voice = $json_config['tts_voice'] ?? '';
        $this->tts_format = $json_config['tts_format'] ?? '';
        $this->vision_model = $json_config['vision_model'] ?? '';

        if (empty($this->base_url) || empty($this->api_key)) {
            throw new invalid_provider_instance_configuration('OpenAI is missing base url and/or api key');
        }

        $this->curl = $this->base_factory->curl();
        $this->curl->setHeader([
            "Authorization: Bearer {$this->api_key}",
            'Content-Type: application/json'
        ]);
    }

    private static function add_chat_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $mform->addElement(
            'text',
            "{$element_name_prefix}chat_model",
            get_string('default_chat_model', 'local_mxaimanager'),
            [
                'action' => interfaces\chat_completion::class
            ]
        );
        $mform->setType("{$element_name_prefix}chat_model", PARAM_TEXT);
        $mform->addHelpButton("{$element_name_prefix}chat_model", 'openai_chat_model', 'local_mxaimanager');
    }

    private static function add_embedding_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $mform->addElement(
            'text',
            "{$element_name_prefix}embedding_model",
            get_string('default_embedding_model', 'local_mxaimanager'),
            [
                'action' => interfaces\create_embedding::class
            ]
        );
        $mform->setType("{$element_name_prefix}embedding_model", PARAM_TEXT);
        $mform->addHelpButton("{$element_name_prefix}embedding_model", 'openai_embedding_model', 'local_mxaimanager');
    }

    private static function add_image_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $mform->addElement(
            'text',
            "{$element_name_prefix}image_model",
            get_string('default_image_model', 'local_mxaimanager'),
            [
                'action' => interfaces\create_image::class
            ]
        );
        $mform->setType("{$element_name_prefix}image_model", PARAM_TEXT);
        $mform->addHelpButton("{$element_name_prefix}image_model", 'openai_image_model', 'local_mxaimanager');
    }

    private static function add_transcription_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $mform->addElement(
            'text',
            "{$element_name_prefix}transcription_model",
            get_string('default_transcription_model', 'local_mxaimanager'),
            [
                'action' => interfaces\create_transcription::class
            ]
        );
        $mform->setType("{$element_name_prefix}transcription_model", PARAM_TEXT);
        $mform->addHelpButton(
            "{$element_name_prefix}transcription_model",
            'openai_transcription_model',
            'local_mxaimanager'
        );
    }

    private static function add_tts_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $mform->addElement(
            'text',
            "{$element_name_prefix}tts_model",
            get_string('default_tts_model', 'local_mxaimanager'),
            [
                'action' => interfaces\create_audio::class
            ]
        );
        $mform->setType("{$element_name_prefix}tts_model", PARAM_TEXT);
        $mform->addHelpButton("{$element_name_prefix}tts_model", 'openai_tts_model', 'local_mxaimanager');
    }

    private static function add_tts_voice_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $voice_options = array_combine(self::TTS_ALLOWED_VOICES, self::TTS_ALLOWED_VOICES);
        $mform->addElement(
            'select',
            "{$element_name_prefix}tts_voice",
            get_string('default_tts_voice', 'local_mxaimanager'),
            $voice_options,
            [
                'action' => interfaces\create_audio::class
            ]
        );
        $mform->setDefault("{$element_name_prefix}tts_voice", self::TTS_DEFAULT_VOICE);
        $mform->addHelpButton("{$element_name_prefix}tts_voice", 'openai_tts_voice', 'local_mxaimanager');
    }

    private static function add_tts_format_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $format_options = array_combine(self::TTS_ALLOWED_FORMATS, self::TTS_ALLOWED_FORMATS);
        $mform->addElement(
            'select',
            "{$element_name_prefix}tts_format",
            get_string('default_tts_format', 'local_mxaimanager'),
            $format_options,
            [
                'action' => interfaces\create_audio::class
            ]
        );
        $mform->setDefault("{$element_name_prefix}tts_format", self::TTS_DEFAULT_FORMAT);
        $mform->addHelpButton("{$element_name_prefix}tts_format", 'openai_tts_format', 'local_mxaimanager');
    }

    private static function add_vision_model_field(\MoodleQuickForm $mform, string $element_name_prefix): void
    {
        $mform->addElement(
            'text',
            "{$element_name_prefix}vision_model",
            get_string('default_vision_model', 'local_mxaimanager'),
            [
                'action' => interfaces\vision::class
            ]
        );
        $mform->setType("{$element_name_prefix}vision_model", PARAM_TEXT);
        $mform->addHelpButton("{$element_name_prefix}vision_model", 'openai_vision_model', 'local_mxaimanager');
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

        // Add TTS fields.
        self::add_tts_model_field($mform, $element_name_prefix);
        self::add_tts_voice_field($mform, $element_name_prefix);
        self::add_tts_format_field($mform, $element_name_prefix);
        self::add_vision_model_field($mform, $element_name_prefix);
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
            case interfaces\create_audio::class:
                self::add_tts_model_field($mform, $element_name_prefix);
                self::add_tts_voice_field($mform, $element_name_prefix);
                self::add_tts_format_field($mform, $element_name_prefix);
                break;
            case interfaces\vision::class:
                self::add_vision_model_field($mform, $element_name_prefix);
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

        $messages = $this->merge_system_messages($messages);

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
                $json['usage']['completion_tokens'],
                $json['choices'][0]['finish_reason'] ?? 'stop'
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
        ];
        if ($dimension !== null && $dimension > 0) {
            $payload['dimensions'] = $dimension;
        }

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
        ];
        if (!$return_b64) {
            throw new \Exception('Openai new API only accepts b64_json');
        }
        try {
            $response = $this->curl->post(
                "{$this->base_url}/v1/images/generations",
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['data'][0]['b64_json'])) {
                throw new \Exception('Missing image data in OpenAI response. OpenAI response: ' . $response);
            }

            return new image_generation_request(
                $payload,
                $json,
                $json['data'][0]['b64_json'],
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

    /**
     * @throws invalid_provider_instance_configuration
     * @throws invalid_provider_instance_response
     */
    public function create_audio(string $text): create_audio_request
    {
        if (empty($this->tts_model)) {
            throw new invalid_provider_instance_configuration('TTS model is not configured');
        }

        $voice = $this->tts_voice !== '' ? $this->tts_voice : self::TTS_DEFAULT_VOICE;
        if (!in_array($voice, self::TTS_ALLOWED_VOICES, true)) {
            throw new invalid_provider_instance_configuration(
                'TTS voice "' . $voice . '" is not supported by OpenAI. Allowed: '
                    . implode(', ', self::TTS_ALLOWED_VOICES)
            );
        }

        $format = $this->tts_format !== '' ? $this->tts_format : self::TTS_DEFAULT_FORMAT;
        if (!in_array($format, self::TTS_ALLOWED_FORMATS, true)) {
            throw new invalid_provider_instance_configuration(
                'TTS format "' . $format . '" is not supported by OpenAI. Allowed: '
                    . implode(', ', self::TTS_ALLOWED_FORMATS)
            );
        }

        $payload = [
            'model' => $this->tts_model,
            'input' => $text,
            'voice' => $voice,
            'response_format' => $format,
        ];

        try {
            $response = $this->curl->post(
                "{$this->base_url}/v1/audio/speech",
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            // OpenAI TTS returns the raw audio bytes (not JSON). If the body parses as JSON
            // with an "error" key, the API rejected the request — surface that.
            $decoded = json_decode($response, true);
            if (is_array($decoded) && isset($decoded['error'])) {
                throw new \Exception('OpenAI TTS error: ' . json_encode($decoded['error']));
            }

            if ($response === '' || $response === false) {
                throw new \Exception('Empty audio response from OpenAI TTS');
            }

            return new create_audio_request(
                $payload,
                [],
                base64_encode($response),
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
    public function vision(string $prompt, array $image_filepaths): vision_request
    {
        if (empty($this->vision_model)) {
            throw new invalid_provider_instance_configuration('Vision model is not configured');
        }

        $payload = [
            'model' => $this->vision_model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => self::build_openai_vision_content($prompt, $image_filepaths),
                ],
            ],
        ];

        try {
            $response = $this->curl->post(
                "{$this->base_url}/v1/chat/completions",
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($json['choices'][0]['message']['content'])) {
                throw new \Exception('Missing content in OpenAI vision response. OpenAI response: ' . $response);
            }

            return new vision_request(
                $payload,
                $json,
                (string) $json['choices'][0]['message']['content'],
                $json['usage']['prompt_tokens'] ?? 0,
                $json['usage']['completion_tokens'] ?? 0
            );
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_response(
                'Invalid response from OpenAI: ' . $t->getMessage(),
                previous: $t
            );
        }
    }
}
