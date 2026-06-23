<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_mxaimanager\app\ai\provider;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

/**
 * Request DTO for text-to-speech generation.
 *
 * `response` is the base64-encoded audio binary. The mimetype is determined by the provider
 * config (`tts_format`) and is the consumer's responsibility to track separately if needed.
 *
 * @package local_mxaimanager
 */
class create_audio_request implements \JsonSerializable
{
    protected array $request_json;
    protected array $response_json;
    protected string $response;
    protected int $input_tokens;
    protected int $output_tokens;

    public function __construct(
        array $request_json,
        array $response_json,
        string $response,
        int $input_tokens,
        int $output_tokens
    ) {
        $this->request_json = $request_json;
        $this->response_json = $response_json;
        $this->response = $response;
        $this->input_tokens = $input_tokens;
        $this->output_tokens = $output_tokens;
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

    public function jsonSerialize(): array
    {
        return [
            'request_json' => $this->get_request_json(),
            'response_json' => $this->get_response_json(),
            'response' => $this->get_response(),
            'input_tokens' => $this->get_input_tokens(),
            'output_tokens' => $this->get_output_tokens(),
        ];
    }
}