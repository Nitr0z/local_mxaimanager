<?php

namespace local_mxaimanager\app\ai\provider;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class chat_completion_request implements \JsonSerializable
{
    protected array $request_json;
    protected array $response_json;
    protected string $response;
    protected int $input_tokens;
    protected int $output_tokens;
    protected string $finish_reason;

    public function __construct(
        array $request_json,
        array $response_json,
        string $response,
        int $input_tokens,
        int $output_tokens,
        string $finish_reason = 'stop'
    ) {
        $this->request_json = $request_json;
        $this->response_json = $response_json;
        $this->response = $response;
        $this->input_tokens = $input_tokens;
        $this->output_tokens = $output_tokens;
        $this->finish_reason = $finish_reason;
    }

    public function get_request_json(): array
    {
        return $this->request_json;
    }

    public function get_response_json(): array
    {
        return $this->response_json;
    }

    public function get_response(): string
    {
        return $this->response;
    }

    public function get_input_tokens(): int
    {
        return $this->input_tokens;
    }

    public function get_output_tokens(): int
    {
        return $this->output_tokens;
    }

    public function get_finish_reason(): string
    {
        return $this->finish_reason;
    }

    public function jsonSerialize(): array
    {
        return [
            'request_json' => $this->get_request_json(),
            'response_json' => $this->get_response_json(),
            'response' => $this->get_response(),
            'input_tokens' => $this->get_input_tokens(),
            'output_tokens' => $this->get_output_tokens(),
            'finish_reason' => $this->get_finish_reason(),
        ];
    }
}
