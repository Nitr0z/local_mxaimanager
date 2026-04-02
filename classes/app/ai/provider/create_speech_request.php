<?php

namespace local_mxaimanager\app\ai\provider;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class create_speech_request implements \JsonSerializable
{
    protected array $request_json;
    protected string $audio_content;
    protected string $content_type;
    protected int $input_tokens;
    protected int $output_tokens;

    public function __construct(
        array $request_json,
        string $audio_content,
        string $content_type,
        int $input_tokens,
        int $output_tokens
    ) {
        $this->request_json = $request_json;
        $this->audio_content = $audio_content;
        $this->content_type = $content_type;
        $this->input_tokens = $input_tokens;
        $this->output_tokens = $output_tokens;
    }

    public function get_request_json(): array
    {
        return $this->request_json;
    }

    /**
     * Get the raw audio binary content.
     */
    public function get_audio_content(): string
    {
        return $this->audio_content;
    }

    /**
     * Get the MIME content type (e.g. audio/mpeg).
     */
    public function get_content_type(): string
    {
        return $this->content_type;
    }

    public function get_input_tokens(): int
    {
        return $this->input_tokens;
    }

    public function get_output_tokens(): int
    {
        return $this->output_tokens;
    }

    public function jsonSerialize(): array
    {
        return [
            'request_json' => $this->get_request_json(),
            'audio_content_length' => strlen($this->audio_content),
            'content_type' => $this->get_content_type(),
            'input_tokens' => $this->get_input_tokens(),
            'output_tokens' => $this->get_output_tokens(),
        ];
    }
}
